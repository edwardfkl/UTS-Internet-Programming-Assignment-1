<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\AdminListRequest;
use App\Support\OrderStock;
use App\Support\PromoCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderStock $orderStock,
    ) {}

    public function index(Request $request): View
    {
        $q = AdminListRequest::search($request);
        $rawStatus = $request->query('status');
        $statusFilter = null;
        if (is_string($rawStatus) && $rawStatus !== '' && in_array($rawStatus, Order::EDITABLE_STATUSES, true)) {
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

        return view('admin.orders.index', [
            'orders' => $orders,
            'sort' => $sort,
            'dir' => $dir,
            'q' => $q,
            'statusFilter' => $statusFilter,
            'statuses' => Order::EDITABLE_STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('admin.orders.create', [
            'users' => User::query()->orderBy('email')->get(['id', 'name', 'email']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'price', 'stock', 'status']),
            'statuses' => Order::EDITABLE_STATUSES,
            'lineRows' => max(count(old('items', [])), 3),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->input('user_id') === '') {
            $request->merge(['user_id' => null]);
        }
        if ($request->input('placed_at') === '') {
            $request->merge(['placed_at' => null]);
        }
        if ($request->input('promo_code') === '') {
            $request->merge(['promo_code' => null]);
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(Order::EDITABLE_STATUSES)],
            'payment_method' => ['nullable', Rule::in(['atm_transfer', 'pay_id', 'bpay'])],
            'placed_at' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'promo_code' => ['nullable', 'string', 'max:32'],
            'shipping_recipient_name' => ['nullable', 'string', 'max:255'],
            'shipping_phone' => ['nullable', 'string', 'max:64'],
            'shipping_line1' => ['nullable', 'string', 'max:255'],
            'shipping_line2' => ['nullable', 'string', 'max:255'],
            'shipping_city' => ['nullable', 'string', 'max:120'],
            'shipping_state' => ['nullable', 'string', 'max:80'],
            'shipping_postcode' => ['nullable', 'string', 'max:32'],
            'shipping_country' => ['nullable', 'string', 'max:120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $quantitiesByProduct = $this->mergedLineQuantities($request);
        if ($quantitiesByProduct === []) {
            throw ValidationException::withMessages([
                'items' => ['Add at least one line with a product and quantity.'],
            ]);
        }

        $products = Product::query()->whereIn('id', array_keys($quantitiesByProduct))->get()->keyBy('id');
        if ($products->count() !== count($quantitiesByProduct)) {
            throw ValidationException::withMessages([
                'items' => ['One or more products could not be found.'],
            ]);
        }

        $subtotal = 0.0;
        foreach ($quantitiesByProduct as $productId => $qty) {
            $subtotal += (float) $products[$productId]->price * $qty;
        }
        $subtotal = round($subtotal, 2);

        $promoNormalised = PromoCode::normalise($data['promo_code'] ?? null);
        $discount = 0.0;
        if ($promoNormalised !== null) {
            $maybeDiscount = PromoCode::discountFor($promoNormalised, $subtotal);
            if ($maybeDiscount === null) {
                throw ValidationException::withMessages([
                    'promo_code' => ['Invalid or expired promo code.'],
                ]);
            }
            $discount = $maybeDiscount;
        }

        $total = round(max($subtotal - $discount, 0), 2);
        $status = $data['status'];
        $placedAt = $data['placed_at'] ?? null;
        if ($placedAt === null && OrderStock::statusReservesStock($status)) {
            $placedAt = now();
        }

        try {
            $order = DB::transaction(function () use ($data, $quantitiesByProduct, $products, $status, $placedAt, $subtotal, $discount, $total, $promoNormalised) {
                $order = Order::query()->create([
                    'user_id' => $data['user_id'] ?? null,
                    'token' => (string) Str::uuid(),
                    'status' => $status,
                    'stock_reserved' => false,
                    'payment_method' => $data['payment_method'] ?? null,
                    'promo_code' => $promoNormalised,
                    'discount_amount' => $discount,
                    'subtotal_amount' => $subtotal,
                    'total_amount' => $total,
                    'placed_at' => $placedAt,
                    'shipping_recipient_name' => $data['shipping_recipient_name'] ?? null,
                    'shipping_phone' => $data['shipping_phone'] ?? null,
                    'shipping_line1' => $data['shipping_line1'] ?? null,
                    'shipping_line2' => $data['shipping_line2'] ?? null,
                    'shipping_city' => $data['shipping_city'] ?? null,
                    'shipping_state' => $data['shipping_state'] ?? null,
                    'shipping_postcode' => $data['shipping_postcode'] ?? null,
                    'shipping_country' => $data['shipping_country'] ?? null,
                ]);

                foreach ($quantitiesByProduct as $productId => $qty) {
                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $productId,
                        'quantity' => $qty,
                        'unit_price' => $products[$productId]->price,
                    ]);
                }

                if (OrderStock::statusReservesStock($status)) {
                    $this->orderStock->reserve($order);
                }

                return $order;
            });
        } catch (ValidationException $e) {
            return redirect()
                ->route('admin.orders.create')
                ->withErrors($e->errors())
                ->withInput();
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Order created.');
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'user']);

        $lineTotal = $order->items->sum(fn ($item) => (float) $item->unit_price * (int) $item->quantity);

        return view('admin.orders.show', [
            'order' => $order,
            'lineTotal' => $lineTotal,
            'statuses' => Order::EDITABLE_STATUSES,
        ]);
    }

    public function edit(Order $order): View
    {
        $order->load('user');
        $users = User::query()->orderBy('email')->get(['id', 'name', 'email']);

        return view('admin.orders.edit', [
            'order' => $order,
            'users' => $users,
            'statuses' => Order::EDITABLE_STATUSES,
        ]);
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
            'status' => ['required', Rule::in(Order::EDITABLE_STATUSES)],
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

        $previousStatus = $order->status;
        $newStatus = $data['status'];

        try {
            DB::transaction(function () use ($order, $data, $previousStatus, $newStatus): void {
                $order->fill([
                    'status' => $newStatus,
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

                $this->orderStock->syncForStatusChange($order, $previousStatus, $newStatus);
            });
        } catch (ValidationException $e) {
            return redirect()
                ->route('admin.orders.edit', $order)
                ->withErrors($e->errors())
                ->withInput();
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order updated.');
    }

    public function destroy(Order $order): RedirectResponse
    {
        DB::transaction(function () use ($order): void {
            $this->orderStock->releaseIfReserved($order);
            $order->items()->delete();
            $order->delete();
        });

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted.');
    }

    /**
     * Bulk delete or status update for the orders list.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['delete', 'set_status'])],
            'status' => ['required_if:action,set_status', Rule::in(Order::EDITABLE_STATUSES)],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:orders,id'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['ids'])));
        $orders = Order::query()->whereIn('id', $ids)->get();
        $applied = 0;

        if ($data['action'] === 'delete') {
            DB::transaction(function () use ($orders, &$applied): void {
                foreach ($orders as $order) {
                    $this->orderStock->releaseIfReserved($order);
                    $order->items()->delete();
                    $order->delete();
                    $applied++;
                }
            });

            return redirect()
                ->route('admin.orders.index', $request->only(['q', 'status', 'sort', 'dir', 'page']))
                ->with('success', __('admin.orders.bulk.deleted', ['count' => $applied]));
        }

        $status = $data['status'];
        foreach ($orders as $order) {
            $previous = $order->status;
            try {
                DB::transaction(function () use ($order, $status, $previous): void {
                    $order->status = $status;
                    $order->save();
                    $this->orderStock->syncForStatusChange($order, $previous, $status);
                });
                $applied++;
            } catch (ValidationException) {
                // Skip orders that cannot reserve stock; continue with the rest.
            }
        }

        return redirect()
            ->route('admin.orders.index', $request->only(['q', 'status', 'sort', 'dir', 'page']))
            ->with('success', __('admin.orders.bulk.status_updated', ['count' => $applied, 'status' => $status]));
    }

    /**
     * @return array<int, int> product_id => quantity
     */
    private function mergedLineQuantities(Request $request): array
    {
        $merged = [];
        foreach ($request->input('items', []) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $productId = (int) ($row['product_id'] ?? 0);
            $qty = (int) ($row['quantity'] ?? 0);
            if ($productId < 1 || $qty < 1) {
                continue;
            }
            $merged[$productId] = ($merged[$productId] ?? 0) + $qty;
        }

        return $merged;
    }
}
