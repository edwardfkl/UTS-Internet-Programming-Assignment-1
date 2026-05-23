@extends('admin.layout')

@section('title', __('admin.orders.create.meta_title'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.orders.index') }}" class="text-sm font-medium text-amber-900 hover:underline">{{ __('admin.orders.create.back') }}</a>
    </div>

    <h1 class="text-2xl font-semibold text-zinc-900">{{ __('admin.orders.create.heading') }}</h1>
    <p class="mt-2 text-sm text-zinc-600">{{ __('admin.orders.create.intro') }}</p>

    <form method="post" action="{{ route('admin.orders.store') }}" class="mt-8 max-w-3xl space-y-8">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="status" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.status') }}</label>
                <select id="status" name="status" required
                        class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption }}" @selected(old('status', \App\Models\Order::STATUS_PENDING_PAYMENT) === $statusOption)>
                            {{ \Illuminate\Support\Facades\Lang::has('admin.orders.status_labels.'.$statusOption) ? __('admin.orders.status_labels.'.$statusOption) : $statusOption }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-zinc-500">{{ __('admin.orders.create.status_hint') }}</p>
            </div>
            <div>
                <label for="payment_method" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.payment_method') }}</label>
                <select id="payment_method" name="payment_method"
                        class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                    <option value="">{{ __('admin.common.none') }}</option>
                    <option value="atm_transfer" @selected(old('payment_method') === 'atm_transfer')>atm_transfer</option>
                    <option value="pay_id" @selected(old('payment_method') === 'pay_id')>pay_id</option>
                    <option value="bpay" @selected(old('payment_method') === 'bpay')>bpay</option>
                </select>
            </div>
            <div>
                <label for="placed_at" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.placed_at') }}</label>
                <input id="placed_at" name="placed_at" type="datetime-local" value="{{ old('placed_at') }}"
                       class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                <p class="mt-1 text-xs text-zinc-500">{{ __('admin.orders.create.placed_at_hint') }}</p>
            </div>
            <div>
                <label for="user_id" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.linked_user') }}</label>
                <select id="user_id" name="user_id" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                    <option value="">{{ __('admin.orders.edit.guest_option') }}</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected((string) old('user_id') === (string) $u->id)>
                            {{ $u->email }} ({{ $u->name }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label for="promo_code" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.create.promo_code') }}</label>
                <input id="promo_code" name="promo_code" type="text" value="{{ old('promo_code') }}"
                       class="mt-1 w-full max-w-xs rounded-lg border border-zinc-300 px-3 py-2 shadow-sm uppercase">
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('admin.orders.create.line_items') }}</h2>
            <p class="mt-1 text-sm text-zinc-600">{{ __('admin.orders.create.line_items_hint') }}</p>
            <div class="mt-4 overflow-x-auto rounded-lg border border-zinc-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 text-left text-zinc-600">
                        <tr>
                            <th class="px-4 py-3">{{ __('admin.orders.show.col_product') }}</th>
                            <th class="px-4 py-3 w-28">{{ __('admin.orders.show.col_qty') }}</th>
                            <th class="px-4 py-3 w-24">{{ __('admin.products.col_stock') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @php
                            $oldItems = old('items', []);
                            $rowCount = max($lineRows, count($oldItems));
                        @endphp
                        @for ($i = 0; $i < $rowCount; $i++)
                            @php
                                $row = $oldItems[$i] ?? [];
                                $selectedProduct = isset($row['product_id']) ? $products->firstWhere('id', (int) $row['product_id']) : null;
                            @endphp
                            <tr>
                                <td class="px-4 py-2">
                                    <select name="items[{{ $i }}][product_id]"
                                            class="w-full min-w-[200px] rounded-lg border border-zinc-300 px-2 py-1.5">
                                        <option value="">{{ __('admin.orders.create.product_placeholder') }}</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}"
                                                    data-stock="{{ $p->stock }}"
                                                    @selected((string) ($row['product_id'] ?? '') === (string) $p->id)>
                                                {{ $p->name }} — ${{ number_format((float) $p->price, 2) }}
                                                @if ($p->status !== \App\Models\Product::STATUS_ACTIVE)
                                                    ({{ __('admin.products.status_labels.'.$p->status) }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-2">
                                    <input name="items[{{ $i }}][quantity]" type="number" min="1"
                                           value="{{ $row['quantity'] ?? '' }}"
                                           class="w-full rounded-lg border border-zinc-300 px-2 py-1.5">
                                </td>
                                <td class="px-4 py-2 tabular-nums text-zinc-600">
                                    @if ($selectedProduct)
                                        {{ $selectedProduct->stock }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('admin.orders.edit.shipping_snapshot') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="shipping_recipient_name" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.recipient') }}</label>
                    <input id="shipping_recipient_name" name="shipping_recipient_name" type="text" value="{{ old('shipping_recipient_name') }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div>
                    <label for="shipping_phone" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.phone') }}</label>
                    <input id="shipping_phone" name="shipping_phone" type="text" value="{{ old('shipping_phone') }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div class="sm:col-span-2">
                    <label for="shipping_line1" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.line1') }}</label>
                    <input id="shipping_line1" name="shipping_line1" type="text" value="{{ old('shipping_line1') }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div class="sm:col-span-2">
                    <label for="shipping_line2" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.line2') }}</label>
                    <input id="shipping_line2" name="shipping_line2" type="text" value="{{ old('shipping_line2') }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div>
                    <label for="shipping_city" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.city') }}</label>
                    <input id="shipping_city" name="shipping_city" type="text" value="{{ old('shipping_city') }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div>
                    <label for="shipping_state" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.state') }}</label>
                    <input id="shipping_state" name="shipping_state" type="text" value="{{ old('shipping_state') }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div>
                    <label for="shipping_postcode" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.postcode') }}</label>
                    <input id="shipping_postcode" name="shipping_postcode" type="text" value="{{ old('shipping_postcode') }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div>
                    <label for="shipping_country" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.country') }}</label>
                    <input id="shipping_country" name="shipping_country" type="text" value="{{ old('shipping_country', 'Australia') }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">
                {{ __('admin.common.create') }}
            </button>
            <a href="{{ route('admin.orders.index') }}" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-50">{{ __('admin.common.cancel') }}</a>
        </div>
    </form>
@endsection
