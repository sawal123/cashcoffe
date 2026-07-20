<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZenzivaOtpService
{
    public function sendOtp(string $target, string $otp): array
    {
        $userkey = config('services.zenziva.userkey');
        $passkey = config('services.zenziva.passkey');

        if (! $userkey || ! $passkey) {
            throw new RuntimeException('Zenziva credentials are not configured.');
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('services.zenziva.timeout', 30))
                ->post(config('services.zenziva.otp_url'), [
                    'userkey' => $userkey,
                    'passkey' => $passkey,
                    'to' => $this->formatTarget($target),
                    'brand' => config('services.zenziva.brand', 'Temuan Space'),
                    'otp' => $otp,
                ])
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Failed to send Zenziva OTP.', previous: $exception);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Invalid Zenziva response.');
        }

        if (($payload['status'] ?? null) !== '1') {
            throw new RuntimeException($payload['text'] ?? 'Zenziva rejected the OTP request.');
        }

        return $payload;
    }

    private function formatTarget(string $target): string
    {
        $target = preg_replace('/\D+/', '', $target) ?? '';

        if (str_starts_with($target, '62')) {
            return '0'.substr($target, 2);
        }

        if (str_starts_with($target, '8')) {
            return '0'.$target;
        }

        return $target;
    }
}
