<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    /**
     * POST /api/promo-codes/preview — validate a promo code without
     * committing it to the order. The frontend uses this to show the
     * estimated discount inline on the checkout page.
     */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ]);

        $normalised = PromoCode::normalise($data['code']);
        $subtotal = (float) $data['subtotal'];
        $discount = $normalised === null
            ? null
            : PromoCode::discountFor($normalised, $subtotal);

        if ($discount === null) {
            return response()->json([
                'valid' => false,
                'code' => $normalised,
                'discount' => 0.0,
                'total' => round($subtotal, 2),
                'message' => 'Invalid or expired promo code.',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'code' => $normalised,
            'discount' => $discount,
            'total' => round(max($subtotal - $discount, 0), 2),
        ]);
    }
}
