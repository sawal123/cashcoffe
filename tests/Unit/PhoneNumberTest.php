<?php

use App\Support\PhoneNumber;

test('member phone numbers are stored without leading zero or country code', function () {
    expect(PhoneNumber::member('082227412345'))->toBe('82227412345')
        ->and(PhoneNumber::member('6282227412345'))->toBe('82227412345')
        ->and(PhoneNumber::member('+62 822-2741-2345'))->toBe('82227412345')
        ->and(PhoneNumber::member('82227412345'))->toBe('82227412345');
});

test('member phone lookup accepts local and whatsapp variants', function () {
    expect(PhoneNumber::lookupValues('082227412345'))->toBe([
        '82227412345',
        '082227412345',
        '6282227412345',
    ]);
});
