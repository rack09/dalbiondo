<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteContact extends Model
{
    public const ID = 1;

    public static function internationalNumber(?string $number): ?string
    {
        if ($number === null || ! preg_match('/\A\+?\(?[0-9][0-9 ()-]*\z/', $number)) {
            return null;
        }

        $compact = str_replace([' ', '(', ')', '-'], '', $number);
        $digits = match (true) {
            str_starts_with($compact, '+') => substr($compact, 1),
            str_starts_with($compact, '00') => substr($compact, 2),
            default => '39'.$compact,
        };

        return preg_match('/\A[1-9][0-9]{6,14}\z/', $digits) ? $digits : null;
    }
}
