<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Adjusts {@see Product::$stock} when orders commit or release inventory.
 *
 * Stock quantity lives on {@code products.stock} (not a separate ledger table).
 * {@see Order::$stock_reserved} records whether this order has already deducted stock.
 */
class OrderStock
{
    /**
     * Statuses that hold stock against the catalogue (everything except draft cart and cancelled).
     */
    public static function statusReservesStock(string $status): bool
    {
        return ! in_array($status, [Order::STATUS_CART, Order::STATUS_CANCELLED], true);
    }

    /**
     * @throws ValidationException
     */
    public function reserve(Order $order): void
    {
        if ($order->stock_reserved) {
            return;
        }

        $order->load('items');

        if ($order->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => ['Add at least one line item before reserving stock.'],
            ]);
        }

        DB::transaction(function () use ($order): void {
            foreach ($order->items as $item) {
                $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->first();
                if ($product === null) {
                    throw ValidationException::withMessages([
                        'items' => ['A line item references a missing product.'],
                    ]);
                }
                if ($product->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        'items' => ["Not enough stock for «{$product->name}» (need {$item->quantity}, have {$product->stock})."],
                    ]);
                }
                $product->decrement('stock', $item->quantity);
            }

            $order->stock_reserved = true;
            $order->save();
        });
    }

    public function release(Order $order): void
    {
        if (! $order->stock_reserved) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $order->load('items');
            foreach ($order->items as $item) {
                Product::query()->whereKey($item->product_id)->increment('stock', $item->quantity);
            }

            $order->stock_reserved = false;
            $order->save();
        });
    }

    /**
     * Call after {@see Order::$status} changes (or when deleting an order).
     */
    public function syncForStatusChange(Order $order, string $previousStatus, string $newStatus): void
    {
        $wasReserving = self::statusReservesStock($previousStatus);
        $willReserve = self::statusReservesStock($newStatus);

        if (! $wasReserving && $willReserve) {
            $this->reserve($order);
        } elseif ($wasReserving && ! $willReserve) {
            $this->release($order);
        }
    }

    public function releaseIfReserved(Order $order): void
    {
        if ($order->stock_reserved) {
            $this->release($order);
        }
    }
}
