<?php

namespace App\Support;

final class Money
{
    /** Format an amount stored in AUD for admin and API display. */
    public static function formatAud(float|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return 'A$0.00';
        }

        return 'A$'.number_format((float) $amount, 2);
    }
}
