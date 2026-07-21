<?php

namespace App\Livewire\Member;

use App\Models\Member;
use App\Models\User;
use App\Models\WhatsappOtp;
use App\Services\ZenzivaOtpService;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Throwable;

class JadiMember extends Component
{
    private const PURPOSE = 'member_registration';

    private const OTP_TTL_MINUTES = 5;

    private const MAX_VERIFY_ATTEMPTS = 5;

    private const RESEND_COOLDOWN_SECONDS = 60;

    public string $step = 'intro';

    public string $name = '';

    public string $phone = '';

    public string $otp = '';

    public string $countryCode = '+62';

    public ?string $otpSentTo = null;

    public ?int $memberId = null;

    public function showForm(): void
    {
        $this->step = 'form';
    }

    public function submitProfile(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'phone.required' => 'Nomor WhatsApp wajib diisi.',
        ]);

        $this->sendOtpToWhatsapp();
        $this->reset('otp');
    }

    public function verifyOtp(): void
    {
        $this->validate([
            'otp' => ['required', 'digits:6'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus 6 digit.',
        ]);

        $otpPhone = $this->otpSentTo ?: PhoneNumber::whatsapp($this->phone);

        DB::transaction(function () use ($otpPhone): void {
            $otpRecord = WhatsappOtp::query()
                ->where('phone', $otpPhone)
                ->where('purpose', self::PURPOSE)
                ->whereNull('used_at')
                ->latest()
                ->lockForUpdate()
                ->first();

            if (! $otpRecord) {
                throw ValidationException::withMessages([
                    'otp' => 'OTP tidak ditemukan.',
                ]);
            }

            if ($otpRecord->isExpired()) {
                $otpRecord->update(['used_at' => now()]);

                throw ValidationException::withMessages([
                    'otp' => 'OTP sudah kedaluwarsa.',
                ]);
            }

            if ($otpRecord->attempts >= self::MAX_VERIFY_ATTEMPTS) {
                $otpRecord->update(['used_at' => now()]);

                throw ValidationException::withMessages([
                    'otp' => 'Percobaan verifikasi OTP sudah mencapai batas.',
                ]);
            }

            if (! Hash::check($this->otp, $otpRecord->otp_hash)) {
                $otpRecord->increment('attempts');

                throw ValidationException::withMessages([
                    'otp' => 'OTP tidak valid.',
                ]);
            }

            $otpRecord->update(['used_at' => now()]);
        });

        $this->memberId = $this->createMemberFromVerifiedPhone(PhoneNumber::member($otpPhone));
        $this->step = 'success';
    }

    public function resendOtp(): void
    {
        $this->sendOtpToWhatsapp();
        $this->reset('otp');
        $this->dispatch('otp-resent');
    }

    public function inviteFriend(): void
    {
        $this->dispatch('member-invite-clicked');
    }

    public function resetFlow(): void
    {
        $this->reset(['step', 'name', 'phone', 'otp', 'otpSentTo', 'memberId']);
        $this->countryCode = '+62';
        $this->step = 'intro';
    }

    private function createMemberFromVerifiedPhone(string $phone): int
    {
        return DB::transaction(function () use ($phone): int {
            $user = User::withTrashed()
                ->whereIn('phone', PhoneNumber::lookupValues($phone))
                ->first();

            if ($user) {
                if ($user->trashed()) {
                    $user->restore();
                }

                $user->update([
                    'name' => $this->name,
                    'phone' => $phone,
                ]);
            } else {
                $user = User::create([
                    'name' => $this->name,
                    'phone' => $phone,
                    'email' => null,
                    'password' => Hash::make(str()->random(32)),
                ]);
            }

            $member = Member::query()
                ->whereIn('phone', PhoneNumber::lookupValues($phone))
                ->orWhere('user_id', $user->id)
                ->first();

            if ($member) {
                $member->update([
                    'user_id' => $user->id,
                    'phone' => $phone,
                ]);
            } else {
                $member = Member::create([
                    'user_id' => $user->id,
                    'phone' => $phone,
                    'points' => 0,
                    'total_pengeluaran' => 0,
                ]);
            }

            return $member->id;
        });
    }

    private function sendOtpToWhatsapp(): void
    {
        $memberPhone = PhoneNumber::member($this->phone);
        $otpPhone = PhoneNumber::whatsapp($memberPhone);

        $latestOtp = WhatsappOtp::query()
            ->where('phone', $otpPhone)
            ->where('purpose', self::PURPOSE)
            ->latest()
            ->first();

        if ($latestOtp && $latestOtp->created_at->gt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))) {
            throw ValidationException::withMessages([
                'phone' => 'Tunggu beberapa saat sebelum mengirim ulang OTP.',
            ]);
        }

        $otp = (string) random_int(100000, 999999);

        $otpRecord = DB::transaction(function () use ($otpPhone, $otp): WhatsappOtp {
            WhatsappOtp::query()
                ->where('phone', $otpPhone)
                ->where('purpose', self::PURPOSE)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            return WhatsappOtp::create([
                'phone' => $otpPhone,
                'purpose' => self::PURPOSE,
                'otp_hash' => Hash::make($otp),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            ]);
        });

        try {
            app(ZenzivaOtpService::class)->sendOtp($otpPhone, $otp);
        } catch (Throwable $exception) {
            $otpRecord->update(['used_at' => now()]);

            Log::warning('Failed to send member WhatsApp OTP.', [
                'phone' => $otpPhone,
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'phone' => $this->getOtpSendErrorMessage($exception),
            ]);
        }

        $this->otpSentTo = $otpPhone;
        $this->step = 'otp';
    }

    private function normalizePhone(string $phone): string
    {
        return PhoneNumber::whatsapp($phone);
    }

    private function buildOtpMessage(string $otp): string
    {
        return "Kode OTP Temuan Space kamu: {$otp}\n\nKode berlaku 5 menit. Jangan bagikan kode ini kepada siapa pun.";
    }

    private function getOtpSendErrorMessage(Throwable $exception): string
    {
        return 'OTP gagal dikirim. Silakan coba lagi.';
    }

    public function render()
    {
        return view('livewire.member.jadi-member')
            ->layout('layouts.member-screen', [
                'title' => 'Jadi Member Temuan Space',
            ]);
    }
}
