<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $token = $request->header('X-Cart-Token');
        if (! $token) {
            abort(401, 'Missing X-Cart-Token header');
        }

        $data = $request->validate([
            'payment_method' => ['required', 'string', 'in:atm_transfer,pay_id,bpay'],
            'shipping_recipient_name' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['required', 'string', 'max:64'],
            'shipping_line1' => ['required', 'string', 'max:255'],
            'shipping_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:120'],
            'shipping_state' => ['required', 'string', 'max:80'],
            'shipping_postcode' => ['required', 'string', 'max:32'],
            'shipping_country' => ['required', 'string', 'max:120'],
            'save_to_profile' => ['sometimes', 'boolean'],
        ]);

        $order = Order::query()
            ->where('token', $token)
            ->withCount('items')
            ->first();

        if (! $order) {
            abort(404, 'Order not found');
        }

        $user = $request->user();
        if ($order->user_id === null) {
            $order->user_id = $user->id;
            $order->save();
        }
        if ((int) $order->user_id !== (int) $user->id) {
            abort(403, 'This cart belongs to another account.');
        }

        if ($order->status !== Order::STATUS_CART) {
            throw ValidationException::withMessages([
                'order' => ['This order has already been submitted.'],
            ]);
        }

        if ($order->items_count === 0) {
            throw ValidationException::withMessages([
                'order' => ['Your cart is empty.'],
            ]);
        }

        $order->status = Order::STATUS_PENDING_PAYMENT;
        $order->payment_method = $data['payment_method'];
        $order->placed_at = now();
        $order->shipping_recipient_name = $data['shipping_recipient_name'];
        $order->shipping_phone = $data['shipping_phone'];
        $order->shipping_line1 = $data['shipping_line1'];
        $order->shipping_line2 = $data['shipping_line2'] ?? null;
        $order->shipping_city = $data['shipping_city'];
        $order->shipping_state = $data['shipping_state'];
        $order->shipping_postcode = $data['shipping_postcode'];
        $order->shipping_country = $data['shipping_country'];
        $order->save();

        if (! empty($data['save_to_profile'])) {
            $user->fill([
                'phone' => $data['shipping_phone'],
                'shipping_recipient_name' => $data['shipping_recipient_name'],
                'shipping_line1' => $data['shipping_line1'],
                'shipping_line2' => $data['shipping_line2'] ?? null,
                'shipping_city' => $data['shipping_city'],
                'shipping_state' => $data['shipping_state'],
                'shipping_postcode' => $data['shipping_postcode'],
                'shipping_country' => $data['shipping_country'],
            ]);
            $user->save();
        }

        $order->load('items.product:id,name,price');
        $lines = $order->items->map(function ($item) {
            $unit = (float) $item->unit_price;

            return [
                'name' => $item->product->name,
                'quantity' => $item->quantity,
                'line_total' => round($unit * $item->quantity, 2),
            ];
        });
        $total = round($lines->sum('line_total'), 2);

        return response()->json([
            'order_reference' => 'SSP-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
            'order_token' => $order->token,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'placed_at' => $order->placed_at?->toIso8601String(),
            'total' => $total,
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
        ], 201);
    }
}
