<?php

use App\Livewire\Payroll\GenerasiGaji;
use Carbon\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('selects the previous month before the cutoff date', function () {
    Carbon::setTestNow(Carbon::create(2026, 6, 23, 12));

    $component = new GenerasiGaji;
    $component->mount();

    expect($component->month)->toBe(5)
        ->and($component->year)->toBe(2026);
});

it('selects the current month when the cutoff period has started', function () {
    Carbon::setTestNow(Carbon::create(2026, 6, 26, 12));

    $component = new GenerasiGaji;
    $component->mount();

    expect($component->month)->toBe(6)
        ->and($component->year)->toBe(2026);
});

it('moves to december of the previous year in early january', function () {
    Carbon::setTestNow(Carbon::create(2026, 1, 10, 12));

    $component = new GenerasiGaji;
    $component->mount();

    expect($component->month)->toBe(12)
        ->and($component->year)->toBe(2025);
});
