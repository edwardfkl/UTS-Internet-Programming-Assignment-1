@extends('admin.layout')

@section('title', __('admin.orders.show.meta_title', ['id' => $order->id]))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <a href="{{ route('admin.orders.index') }}" class="text-sm font-medium text-amber-900 hover:underline">{{ __('admin.orders.show.back') }}</a>
        <a href="{{ route('admin.orders.edit', $order) }}" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">{{ __('admin.orders.show.btn_edit') }}</a>
    </div>

    <h1 class="text-2xl font-semibold text-zinc-900">{{ __('admin.orders.show.heading', ['id' => $order->id]) }}</h1>

    <dl class="mt-6 grid max-w-2xl gap-4 sm:grid-cols-2 text-sm">
        <div>
            <dt class="font-medium text-zinc-600">{{ __('admin.orders.show.status') }}</dt>
            <dd class="mt-1 text-zinc-900">{{ \Illuminate\Support\Facades\Lang::has('admin.orders.status_labels.'.$order->status) ? __('admin.orders.status_labels.'.$order->status) : $order->status }}</dd>
        </div>
        <div>
            <dt class="font-medium text-zinc-600">{{ __('admin.orders.show.cart_token') }}</dt>
            <dd class="mt-1 break-all font-mono text-xs text-zinc-800">{{ $order->token }}</dd>
        </div>
        <div>
            <dt class="font-medium text-zinc-600">{{ __('admin.orders.show.user') }}</dt>
            <dd class="mt-1 text-zinc-900">
                @if ($order->user)
                    {{ $order->user->name }} ({{ $order->user->email }})
                @else
                    {{ __('admin.orders.guest') }}
                @endif
            </dd>
        </div>
        <div>
            <dt class="font-medium text-zinc-600">{{ __('admin.orders.show.payment_method') }}</dt>
            <dd class="mt-1 text-zinc-900">{{ $order->payment_method ?? __('admin.common.none') }}</dd>
        </div>
        <div>
            <dt class="font-medium text-zinc-600">{{ __('admin.orders.show.placed_at') }}</dt>
            <dd class="mt-1 text-zinc-900"><x-local-datetime :at="$order->placed_at" :with-seconds="true" :fallback="__('admin.common.none')"/></dd>
        </div>
        <div>
            <dt class="font-medium text-zinc-600">{{ __('admin.orders.show.promo_code') }}</dt>
            <dd class="mt-1 text-zinc-900 font-mono text-xs">{{ $order->promo_code ?? __('admin.common.none') }}</dd>
        </div>
        <div>
            <dt class="font-medium text-zinc-600">{{ __('admin.orders.show.total') }}</dt>
            <dd class="mt-1 text-zinc-900 tabular-nums">
                <x-aud-money :amount="$order->subtotal_amount" />
                @if ((float) $order->discount_amount > 0)
                    − <x-aud-money :amount="$order->discount_amount" />
                @endif
                = <strong><x-aud-money :amount="$order->total_amount" /></strong>
            </dd>
        </div>
    </dl>

    @if ($order->shipping_line1)
        <h2 class="mt-10 text-lg font-semibold text-zinc-900">{{ __('admin.orders.show.shipping') }}</h2>
        <address class="mt-2 text-sm not-italic text-zinc-800">
            {{ $order->shipping_recipient_name }}<br>
            @if ($order->shipping_phone) {{ $order->shipping_phone }}<br> @endif
            {{ $order->shipping_line1 }}<br>
            @if ($order->shipping_line2) {{ $order->shipping_line2 }}<br> @endif
            {{ $order->shipping_city }} {{ $order->shipping_state }} {{ $order->shipping_postcode }}<br>
            {{ $order->shipping_country }}
        </address>
    @endif

    <h2 class="mt-10 text-lg font-semibold text-zinc-900">{{ __('admin.orders.show.line_items') }}</h2>
    <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wide text-zinc-600">
            <tr>
                <th class="px-4 py-3">{{ __('admin.orders.show.col_product') }}</th>
                <th class="px-4 py-3">{{ __('admin.orders.show.col_qty') }}</th>
                <th class="px-4 py-3">{{ __('admin.orders.show.col_unit') }}</th>
                <th class="px-4 py-3 text-right">{{ __('admin.orders.show.col_line_total') }}</th>
                <th class="px-4 py-3 text-right">{{ __('admin.orders.show.col_actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
            @foreach ($order->items as $item)
                <tr>
                    <td class="px-4 py-3">
                        #{{ $item->product_id }}
                        @if ($item->product)
                            — {{ $item->product->name }}
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ $item->quantity }}</td>
                    <td class="whitespace-nowrap px-4 py-3 tabular-nums"><x-aud-money :amount="$item->unit_price" /></td>
                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">
                        <x-aud-money :amount="(float) $item->unit_price * (int) $item->quantity" />
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right">
                        <a href="{{ route('admin.order-items.edit', $item) }}" class="font-medium text-amber-900 hover:underline">{{ __('admin.orders.show.edit_link') }}</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
            <tr class="bg-zinc-50 font-medium">
                <td colspan="4" class="px-4 py-3 text-right">{{ __('admin.orders.show.subtotal') }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums"><x-aud-money :amount="$lineTotal" /></td>
            </tr>
            </tfoot>
        </table>
    </div>
@endsection
