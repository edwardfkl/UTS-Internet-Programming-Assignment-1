@extends('admin.layout')

@section('title', __('admin.orders.edit.meta_title', ['id' => $order->id]))

@section('content')
    <div class="mb-6 flex flex-wrap gap-4">
        <a href="{{ route('admin.orders.show', $order) }}" class="text-sm font-medium text-amber-900 hover:underline">{{ __('admin.orders.edit.back_order') }}</a>
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-zinc-600 hover:underline">{{ __('admin.orders.edit.all_orders') }}</a>
    </div>

    <h1 class="text-2xl font-semibold text-zinc-900">{{ __('admin.orders.edit.heading', ['id' => $order->id]) }}</h1>
    <p class="mt-2 text-sm text-zinc-600">{{ __('admin.orders.edit.token_note') }} <code class="rounded bg-zinc-100 px-1 text-xs">{{ $order->token }}</code></p>

    <form method="post" action="{{ route('admin.orders.update', $order) }}" class="mt-8 max-w-2xl space-y-6">
        @csrf
        @method('put')

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="status" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.status') }}</label>
                <select id="status" name="status" required
                        class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption }}" @selected(old('status', $order->status) === $statusOption)>
                            {{ \Illuminate\Support\Facades\Lang::has('admin.orders.status_labels.'.$statusOption) ? __('admin.orders.status_labels.'.$statusOption) : $statusOption }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-zinc-500">{{ __('admin.orders.edit.status_flow_hint') }}</p>
            </div>
            <div>
                <label for="payment_method" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.payment_method') }}</label>
                <select id="payment_method" name="payment_method"
                        class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                    <option value="">{{ __('admin.common.none') }}</option>
                    <option value="atm_transfer" @selected(old('payment_method', $order->payment_method) === 'atm_transfer')>atm_transfer</option>
                    <option value="pay_id" @selected(old('payment_method', $order->payment_method) === 'pay_id')>pay_id</option>
                    <option value="bpay" @selected(old('payment_method', $order->payment_method) === 'bpay')>bpay</option>
                </select>
            </div>
            <div>
                <label for="placed_at" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.placed_at') }}</label>
                <input id="placed_at" name="placed_at" type="datetime-local"
                       value="{{ old('placed_at', $order->placed_at?->format('Y-m-d\TH:i')) }}"
                       class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
            </div>
            <div>
                <label for="user_id" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.linked_user') }}</label>
                <select id="user_id" name="user_id" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                    <option value="">{{ __('admin.orders.edit.guest_option') }}</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected((string) old('user_id', $order->user_id) === (string) $u->id)>
                            {{ $u->email }} ({{ $u->name }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('admin.orders.edit.shipping_snapshot') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="shipping_recipient_name" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.recipient') }}</label>
                    <input id="shipping_recipient_name" name="shipping_recipient_name" type="text"
                           value="{{ old('shipping_recipient_name', $order->shipping_recipient_name) }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div>
                    <label for="shipping_phone" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.phone') }}</label>
                    <input id="shipping_phone" name="shipping_phone" type="text"
                           value="{{ old('shipping_phone', $order->shipping_phone) }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div class="sm:col-span-2">
                    <label for="shipping_line1" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.line1') }}</label>
                    <input id="shipping_line1" name="shipping_line1" type="text"
                           value="{{ old('shipping_line1', $order->shipping_line1) }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div class="sm:col-span-2">
                    <label for="shipping_line2" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.line2') }}</label>
                    <input id="shipping_line2" name="shipping_line2" type="text"
                           value="{{ old('shipping_line2', $order->shipping_line2) }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div>
                    <label for="shipping_city" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.city') }}</label>
                    <input id="shipping_city" name="shipping_city" type="text"
                           value="{{ old('shipping_city', $order->shipping_city) }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div>
                    <label for="shipping_state" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.state') }}</label>
                    <input id="shipping_state" name="shipping_state" type="text"
                           value="{{ old('shipping_state', $order->shipping_state) }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div>
                    <label for="shipping_postcode" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.postcode') }}</label>
                    <input id="shipping_postcode" name="shipping_postcode" type="text"
                           value="{{ old('shipping_postcode', $order->shipping_postcode) }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div>
                    <label for="shipping_country" class="block text-sm font-medium text-zinc-700">{{ __('admin.orders.edit.country') }}</label>
                    <input id="shipping_country" name="shipping_country" type="text"
                           value="{{ old('shipping_country', $order->shipping_country) }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">
                {{ __('admin.orders.edit.save_order') }}
            </button>
            <a href="{{ route('admin.orders.show', $order) }}" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-50">{{ __('admin.orders.edit.cancel') }}</a>
        </div>
    </form>
@endsection
