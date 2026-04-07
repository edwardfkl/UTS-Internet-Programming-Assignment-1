<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartAttachController extends Controller
{
    /**
     * Links the current X-Cart-Token draft order to the authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $token = $request->header('X-Cart-Token');
        if (! $token) {
            abort(401, 'Missing X-Cart-Token header');
        }

        $order = Order::query()->where('token', $token)->first();
        if (! $order) {
            abort(404, 'Order not found');
        }

        if ($order->status !== Order::STATUS_CART) {
            throw ValidationException::withMessages([
                'order' => ['Only an open cart can be linked to your account.'],
            ]);
        }

        if ($order->user_id !== null && (int) $order->user_id !== (int) $request->user()->id) {
            abort(403, 'This cart belongs to another account.');
        }

        $order->user_id = $request->user()->id;
        $order->save();

        return response()->json(['ok' => true]);
    }
}
