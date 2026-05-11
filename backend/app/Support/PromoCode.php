<?php

namespace App\Support;

use App\Models\PromoCode as PromoCodeModel;

/**
 * Thin facade over the {@see PromoCodeModel} so callers can resolve a
 * discount by code without coupling to Eloquent. Codes are matched
 * case-insensitively against the stored uppercase value.
 */
class PromoCode
{
    public static function normalise(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }
        $trimmed = trim($code);
        if ($trimmed === '') {
            return null;
        }

        return strtoupper($trimmed);
    }

    /**
     * Returns the discount amount for the given code (clamped to the
     * subtotal), or `null` if the code is unknown / not currently
     * redeemable / fails the minimum-subtotal requirement.
     */
    public static function discountFor(?string $code, float $subtotal): ?float
    {
        $normalised = self::normalise($code);
        if ($normalised === null) {
            return null;
        }

        $row = PromoCodeModel::query()
            ->where('code', $normalised)
            ->first();

        if ($row === null) {
            return null;
        }

        return $row->discountFor($subtotal);
    }

    public static function exists(?string $code): bool
    {
        $normalised = self::normalise($code);
        if ($normalised === null) {
            return false;
        }

        return PromoCodeModel::query()->where('code', $normalised)->exists();
    }
}
