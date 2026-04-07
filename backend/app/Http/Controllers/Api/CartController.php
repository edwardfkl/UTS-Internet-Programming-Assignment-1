<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesOrderAccess;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    use AuthorizesOrderAccess;

    private function orderFromRequest(Request $request): Order
    {
        $token = $request->header('X-Cart-Token');
        if (! $token) {
            abort(401, 'Missing X-Cart-Token header');
        }
        $order = Order::query()->where('token', $token)->first();
        if (! $order) {
            abort(404, 'Order not found');
        }
        $this->assertOrderAccessible($request, $order);

        return $order;
    }

    private function assertCartMutable(Order $order): void
    {
        if ($order->status !== Order::STATUS_CART) {
            abort(409, 'This order has already been submitted and cannot be changed.');
        }
    }

    public function show(Request $request): JsonResponse
    {
        $order = $this->orderFromRequest($request);
        $items = OrderItem::query()
            ->where('order_id', $order->id)
            ->with(['product:id,name,description,price,image_url,stock'])
            ->get();

        $lines = $items->map(function (OrderItem $item) {
            $unit = (float) $item->unit_price;

            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'line_total' => round($unit * $item->quantity, 2),
                'product' => $item->product,
            ];
        });

        $total = round($lines->sum('line_total'), 2);

        return response()->json([
            'status' => $order->status,
            'items' => $lines,
            'total' => $total,
        ]);
    }

    public function storeItem(Request $request): JsonResponse
    {
        $order = $this->orderFromRequest($request);
        $this->assertCartMutable($order);
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::query()->findOrFail($data['product_id']);
        if ($product->stock < $data['quantity']) {
            throw ValidationException::withMessages([
                'quantity' => ['Not enough stock for this product.'],
            ]);
        }

        $item = OrderItem::query()->firstOrNew([
            'order_id' => $order->id,
            'product_id' => $data['product_id'],
        ]);
        if (! $item->exists) {
            $item->unit_price = $product->price;
        }
        $currentQty = $item->exists ? (int) $item->quantity : 0;
        $newQty = $currentQty + $data['quantity'];
        if ($product->stock < $newQty) {
            throw ValidationException::withMessages([
                'quantity' => ['Not enough stock for this quantity in cart.'],
            ]);
        }
        $item->quantity = $newQty;
        $item->save();
        $item->load(['product:id,name,description,price,image_url,stock']);

        $unit = (float) $item->unit_price;

        return response()->json([
            'id' => $item->id,
            'quantity' => $item->quantity,
            'line_total' => round($unit * $item->quantity, 2),
            'product' => $item->product,
        ], 201);
    }

    public function updateItem(Request $request, int $cartItem): JsonResponse
    {
        $order = $this->orderFromRequest($request);
        $this->assertCartMutable($order);
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item = OrderItem::query()
            ->where('order_id', $order->id)
            ->where('id', $cartItem)
            ->with(['product'])
            ->firstOrFail();

        if ($item->product->stock < $data['quantity']) {
            throw ValidationException::withMessages([
                'quantity' => ['Not enough stock for this product.'],
            ]);
        }

        $item->quantity = $data['quantity'];
        $item->save();
        $item->load(['product:id,name,description,price,image_url,stock']);
        $unit = (float) $item->unit_price;

        return response()->json([
            'id' => $item->id,
            'quantity' => $item->quantity,
            'line_total' => round($unit * $item->quantity, 2),
            'product' => $item->product,
        ]);
    }

    public function destroyItem(Request $request, int $cartItem): Response
    {
        $order = $this->orderFromRequest($request);
        $this->assertCartMutable($order);
        $deleted = OrderItem::query()
            ->where('order_id', $order->id)
            ->where('id', $cartItem)
            ->delete();

        if ($deleted === 0) {
            abort(404);
        }

        return response()->noContent();
    }
}
