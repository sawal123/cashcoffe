<?php

namespace App\Support;

class PhoneNumber
{
    public static function member(?string $phone): string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (str_starts_with($phone, '620')) {
            return substr($phone, 3);
        }

        if (str_starts_with($phone, '62')) {
            return substr($phone, 2);
        }

        if (str_starts_with($phone, '0')) {
            return substr($phone, 1);
        }

        return $phone;
    }

    public static function whatsapp(?string $phone): string
    {
        $memberPhone = self::member($phone);

        return $memberPhone === '' ? '' : '62' . $memberPhone;
    }

    public static function lookupValues(?string $phone): array
    {
        $memberPhone = self::member($phone);

        if ($memberPhone === '') {
            return [];
        }

        return array_values(array_unique([
            $memberPhone,
            '0' . $memberPhone,
            '62' . $memberPhone,
        ]));
    }
}
