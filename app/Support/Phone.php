<?php

namespace App\Support;

class Phone
{
    /** 0300-1234567 and +92 300 1234567 are the same parent: store one form. */
    public static function normalize(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '92') && strlen($digits) === 12) {
            return '0'.substr($digits, 2);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '3')) {
            return '0'.$digits;
        }

        return $digits;
    }

    public static function international(?string $phone): string
    {
        $digits = static::normalize($phone);

        return str_starts_with($digits, '0') ? '92'.substr($digits, 1) : $digits;
    }
}
