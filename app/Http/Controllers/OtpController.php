<?php

namespace App\Http\Controllers;

use App\Models\WhatsappOtp;
use App\Services\ZenzivaOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class OtpController extends Controller
{
    private const PURPOSE_MEMBER_REGISTRATION = 'member_registration';

    private const OTP_TTL_MINUTES = 5;

    private const MAX_VERIFY_ATTEMPTS = 5;

    private const RESEND_COOLDOWN_SECONDS = 60;

    public function send(Request $request, ZenzivaOtpService $zenziva): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'purpose' => ['nullable', 'string', 'max:64'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);
        $purpose = $validated['purpose'] ?? self::PURPOSE_MEMBER_REGISTRATION;

        $latestOtp = WhatsappOtp::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->latest()
            ->first();

        if ($latestOtp && $latestOtp->created_at->gt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))) {
            throw ValidationException::withMessages([
                'phone' => 'Tunggu beberapa saat sebelum mengirim ulang OTP.',
            ]);
        }

        $otp = (string) random_int(100000, 999999);

        $otpRecord = DB::transaction(function () use ($phone, $purpose, $otp): WhatsappOtp {
            WhatsappOtp::query()
                ->where('phone', $phone)
                ->where('purpose', $purpose)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            return WhatsappOtp::create([
                'phone' => $phone,
                'purpose' => $purpose,
                'otp_hash' => Hash::make($otp),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            ]);
        });

        try {
            $zenziva->sendOtp($phone, $otp);
        } catch (Throwable $exception) {
            $otpRecord->update(['used_at' => now()]);

            Log::warning('Failed to send WhatsApp OTP.', [
                'phone' => $phone,
                'purpose' => $purpose,
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'phone' => 'OTP gagal dikirim. Silakan coba lagi.',
            ]);
        }

        return response()->json([
            'message' => 'OTP berhasil dikirim.',
            'expires_in' => self::OTP_TTL_MINUTES * 60,
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'otp' => ['required', 'digits:6'],
            'purpose' => ['nullable', 'string', 'max:64'],
        ]);

        $phone = $this->normalizePhone($validated['phone']);
        $purpose = $validated['purpose'] ?? self::PURPOSE_MEMBER_REGISTRATION;

        $verified = DB::transaction(function () use ($phone, $purpose, $validated): bool {
            $otpRecord = WhatsappOtp::query()
                ->where('phone', $phone)
                ->where('purpose', $purpose)
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

            if (! Hash::check($validated['otp'], $otpRecord->otp_hash)) {
                $otpRecord->increment('attempts');

                throw ValidationException::withMessages([
                    'otp' => 'OTP tidak valid.',
                ]);
            }

            $otpRecord->update([
                'used_at' => now(),
            ]);

            return true;
        });

        return response()->json([
            'verified' => $verified,
            'message' => 'OTP berhasil diverifikasi.',
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($phone, '0')) {
            return '62'.substr($phone, 1);
        }

        if (str_starts_with($phone, '620')) {
            return '62'.substr($phone, 3);
        }

        return $phone;
    }

    private function buildOtpMessage(string $otp): string
    {
        return "Kode OTP Temuan Space kamu: {$otp}\n\nKode berlaku 5 menit. Jangan bagikan kode ini kepada siapa pun.";
    }
}
