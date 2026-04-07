<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\SanctumBearer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartSessionController extends Controller
{
    /**
     * Creates a draft order (status cart) for a guest checkout token.
     * Kept as POST /api/cart/sessions so the SPA contract stays unchanged.
     */
    public function store(Request $request): JsonResponse
    {
        $user = SanctumBearer::user($request);
        $order = Order::query()->create([
            'user_id' => $user?->id,
            'token' => (string) Str::uuid(),
            'status' => Order::STATUS_CART,
        ]);

        return response()->json([
            'token' => $order->token,
        ], 201);
    }
}
