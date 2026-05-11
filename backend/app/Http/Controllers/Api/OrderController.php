<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * GET /api/orders — list orders that belong to the authenticated user.
     *
     * Excludes draft carts (status = cart) so /account/orders only shows
     * orders that the customer has actually placed.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', Order::STATUS_CART)
            ->withCount('items')
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get([
                'id',
                'status',
                'payment_method',
                'promo_code',
                'discount_amount',
                'subtotal_amount',
                'total_amount',
                'placed_at',
                'created_at',
            ]);

        $rows = $orders->map(fn (Order $order) => [
            'id' => $order->id,
            'reference' => 'SSP-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'promo_code' => $order->promo_code,
            'discount_amount' => (float) $order->discount_amount,
            'subtotal_amount' => (float) $order->subtotal_amount,
            'total_amount' => (float) $order->total_amount,
            'items_count' => (int) ($order->items_count ?? 0),
            'placed_at' => $order->placed_at?->toIso8601String(),
            'created_at' => $order->created_at?->toIso8601String(),
        ]);

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /api/orders/{order} — fetch a single order owned by the user.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        if ((int) $order->user_id !== (int) $user->id) {
            abort(403, 'This order does not belong to your account.');
        }

        if ($order->status === Order::STATUS_CART) {
            abort(404, 'Order not found.');
        }

        $order->load('items.product:id,name,price,image_url');

        $lines = $order->items->map(function ($item) {
            $unit = (float) $item->unit_price;

            return [
                'id' => $item->id,
                'quantity' => (int) $item->quantity,
                'unit_price' => $unit,
                'line_total' => round($unit * (int) $item->quantity, 2),
                'product' => $item->product,
            ];
        });

        return response()->json([
            'id' => $order->id,
            'reference' => 'SSP-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'promo_code' => $order->promo_code,
            'discount_amount' => (float) $order->discount_amount,
            'subtotal_amount' => (float) $order->subtotal_amount,
            'total_amount' => (float) $order->total_amount,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'lines' => $lines,
            'shipping' => [
                'recipient_name' => $order->shipping_recipient_name,
                'phone' => $order->shipping_phone,
                'line1' => $order->shipping_line1,
                'line2' => $order->shipping_line2,
                'city' => $order->shipping_city,
                'state' => $order->shipping_state,
                'postcode' => $order->shipping_postcode,
                'country' => $order->shipping_country,
            ],
        ]);
    }
}
