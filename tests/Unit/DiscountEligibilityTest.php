<?php

use App\Models\Discount;

test('member only discounts require a member id', function () {
    $discount = new Discount(['member_only' => true]);

    expect($discount->canBeUsedByMemberId(null))->toBeFalse()
        ->and($discount->canBeUsedByMemberId(7))->toBeTrue();
});

test('non member only discounts can be used without a member id', function () {
    $discount = new Discount(['member_only' => false]);

    expect($discount->canBeUsedByMemberId(null))->toBeTrue();
});
