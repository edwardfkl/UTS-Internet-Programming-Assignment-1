<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Support\AdminListRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $q = AdminListRequest::search($request);
        $rawStatus = $request->query('status');
        $statusFilter = null;
        if (is_string($rawStatus) && $rawStatus !== '' && in_array($rawStatus, [Order::STATUS_CART, Order::STATUS_PENDING_PAYMENT], true)) {
            $statusFilter = $rawStatus;
        }

        [$sort, $dir] = AdminListRequest::sort(
            $request,
            ['id', 'status', 'placed_at', 'created_at', 'updated_at'],
            'updated_at',
            'desc',
        );

        $query = Order::query()
            ->with('user')
            ->withCount('items');

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        if ($q !== null) {
            $like = '%'.$q.'%';
            $query->where(function ($sub) use ($q, $like) {
                if (ctype_digit($q)) {
                    $sub->whereKey((int) $q);
                }
                $sub->orWhere('token', 'like', $like)
                    ->orWhereHas('user', function ($u) use ($like) {
                        $u->where('email', 'like', $like)
                            ->orWhere('name', 'like', $like);
                    });
            });
        }

        if ($sort === 'placed_at') {
            $query->orderByRaw('placed_at IS NULL ASC')->orderBy('placed_at', $dir);
        } else {
            $query->orderBy($sort, $dir);
        }

        $orders = $query->paginate(25)->withQueryString();

        return view('admin.orders.index', compact('orders', 'sort', 'dir', 'q', 'statusFilter'));
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'user']);

        $lineTotal = $order->items->sum(fn ($item) => (float) $item->unit_price * (int) $item->quantity);

        return view('admin.orders.show', [
            'order' => $order,
            'lineTotal' => $lineTotal,
        ]);
    }

    public function edit(Order $order): View
    {
        $order->load('user');
        $users = User::query()->orderBy('email')->get(['id', 'name', 'email']);

        return view('admin.orders.edit', compact('order', 'users'));
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        if ($request->input('user_id') === '') {
            $request->merge(['user_id' => null]);
        }
        if ($request->input('placed_at') === '') {
            $request->merge(['placed_at' => null]);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in([Order::STATUS_CART, Order::STATUS_PENDING_PAYMENT])],
            'payment_method' => ['nullable', Rule::in(['atm_transfer', 'pay_id', 'bpay'])],
            'placed_at' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'shipping_recipient_name' => ['nullable', 'string', 'max:255'],
            'shipping_phone' => ['nullable', 'string', 'max:64'],
            'shipping_line1' => ['nullable', 'string', 'max:255'],
            'shipping_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['nullable', 'string', 'max:120'],
            'shipping_state' => ['nullable', 'string', 'max:80'],
            'shipping_postcode' => ['nullable', 'string', 'max:32'],
            'shipping_country' => ['nullable', 'string', 'max:120'],
        ]);

        $order->fill([
            'status' => $data['status'],
            'payment_method' => $data['payment_method'] ?? null,
            'placed_at' => $data['placed_at'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'shipping_recipient_name' => $data['shipping_recipient_name'] ?? null,
            'shipping_phone' => $data['shipping_phone'] ?? null,
            'shipping_line1' => $data['shipping_line1'] ?? null,
            'shipping_line2' => $data['shipping_line2'] ?? null,
            'shipping_city' => $data['shipping_city'] ?? null,
            'shipping_state' => $data['shipping_state'] ?? null,
            'shipping_postcode' => $data['shipping_postcode'] ?? null,
            'shipping_country' => $data['shipping_country'] ?? null,
        ]);
        $order->save();

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order updated.');
    }
}
