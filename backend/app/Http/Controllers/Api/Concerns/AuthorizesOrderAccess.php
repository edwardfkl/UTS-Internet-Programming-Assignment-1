<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Order;
use App\Support\SanctumBearer;
use Illuminate\Http\Request;

trait AuthorizesOrderAccess
{
    protected function assertOrderAccessible(Request $request, Order $order): void
    {
        $user = SanctumBearer::user($request);
        if ($order->user_id === null) {
            return;
        }
        if (! $user || (int) $user->id !== (int) $order->user_id) {
            abort(403, 'This cart belongs to another account.');
        }
    }
}
