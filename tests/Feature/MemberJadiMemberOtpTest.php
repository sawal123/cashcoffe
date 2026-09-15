<?php

use App\Livewire\Member\JadiMember;
use App\Models\WhatsappOtp;
use App\Services\ZenzivaOtpService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('shows a specific message when the Zenziva WhatsApp device is disconnected', function () {
    $zenziva = \Mockery::mock(ZenzivaOtpService::class);
    $zenziva->shouldReceive('sendOtp')
        ->once()
        ->andThrow(new RuntimeException('request invalid on disconnected device'));

    app()->instance(ZenzivaOtpService::class, $zenziva);

    Livewire::test(JadiMember::class)
        ->call('showForm')
        ->set('name', 'Gotik')
        ->set('phone', '082372025182')
        ->call('submitProfile')
        ->assertHasErrors('phone')
        ->assertSee('Layanan WhatsApp OTP sedang tidak terhubung. Hubungi admin.');

    $otp = WhatsappOtp::query()->first();

    expect($otp)->not->toBeNull()
        ->and($otp->phone)->toBe('6282372025182')
        ->and($otp->used_at)->not->toBeNull();
});

it('accepts a numeric success status from Zenziva', function () {
    Config::set('services.zenziva.userkey', 'test-userkey');
    Config::set('services.zenziva.passkey', 'test-passkey');
    Config::set('services.zenziva.brand', 'Temuan Space');
    Config::set('services.zenziva.otp_url', 'https://zenziva.test/otp');

    Http::fake([
        'zenziva.test/*' => Http::response([
            'status' => 1,
            'text' => 'OTP sent',
        ]),
    ]);

    $payload = app(ZenzivaOtpService::class)->sendOtp('6282372025182', '123456');

    expect($payload['status'])->toBe(1);

    Http::assertSent(fn ($request) => $request->url() === 'https://zenziva.test/otp'
        && $request['userkey'] === 'test-userkey'
        && $request['passkey'] === 'test-passkey'
        && $request['to'] === '082372025182'
        && $request['brand'] === 'Temuan Space'
        && $request['otp'] === '123456');
});
