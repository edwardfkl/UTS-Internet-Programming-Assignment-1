@extends('admin.layout')

@section('title', __('admin.orders.title'))

@section('content')
    <h1 class="text-2xl font-semibold text-zinc-900">{{ __('admin.orders.heading') }}</h1>
    <p class="mt-2 text-sm text-zinc-600">{{ __('admin.orders.intro') }}</p>

    <form method="get" action="{{ route('admin.orders.index') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="dir" value="{{ $dir }}">
        <div class="min-w-[180px]">
            <label for="orders-status" class="mb-1 block text-xs font-medium text-zinc-600">{{ __('admin.orders.filter_status') }}</label>
            <select id="orders-status" name="status"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-amber-900 focus:outline-none focus:ring-1 focus:ring-amber-900">
                <option value="" @selected($statusFilter === null)>{{ __('admin.orders.filter_status_all') }}</option>
                <option value="cart" @selected($statusFilter === 'cart')>{{ __('admin.orders.filter_status_cart') }}</option>
                <option value="pending_payment" @selected($statusFilter === 'pending_payment')>{{ __('admin.orders.filter_status_pending') }}</option>
            </select>
        </div>
        <div class="min-w-[200px] flex-1">
            <label for="orders-q" class="mb-1 block text-xs font-medium text-zinc-600">{{ __('admin.common.search') }}</label>
            <input id="orders-q" type="search" name="q" value="{{ $q ?? '' }}"
                   placeholder="{{ __('admin.orders.search_placeholder') }}"
                   class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-amber-900 focus:outline-none focus:ring-1 focus:ring-amber-900">
        </div>
        <button type="submit"
                class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">{{ __('admin.common.search_submit') }}</button>
        @if (! empty($q) || $statusFilter !== null)
            <a href="{{ route('admin.orders.index', ['sort' => $sort, 'dir' => $dir]) }}"
               class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-800 hover:bg-zinc-50">{{ __('admin.common.clear') }}</a>
        @endif
    </form>

    <div class="mt-8 overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wide text-zinc-600">
            <tr>
                <x-admin.sort-th :label="__('admin.orders.col_id')" column="id" :sort="$sort" :dir="$dir" route="admin.orders.index" :preserve-query="['q', 'status']"/>
                <th class="px-4 py-3">{{ __('admin.orders.col_token') }}</th>
                <x-admin.sort-th :label="__('admin.orders.col_status')" column="status" :sort="$sort" :dir="$dir" route="admin.orders.index" :preserve-query="['q', 'status']"/>
                <th class="px-4 py-3">{{ __('admin.orders.col_user') }}</th>
                <th class="px-4 py-3">{{ __('admin.orders.col_lines') }}</th>
                <x-admin.sort-th :label="__('admin.orders.col_placed')" column="placed_at" :sort="$sort" :dir="$dir" route="admin.orders.index" :preserve-query="['q', 'status']"/>
                <x-admin.sort-th :label="__('admin.orders.col_created')" column="created_at" :sort="$sort" :dir="$dir" route="admin.orders.index" :preserve-query="['q', 'status']"/>
                <x-admin.sort-th :label="__('admin.orders.col_updated')" column="updated_at" :sort="$sort" :dir="$dir" route="admin.orders.index" :preserve-query="['q', 'status']"/>
                <th class="px-4 py-3 text-right">{{ __('admin.orders.col_view_edit') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
            @forelse ($orders as $order)
                <tr class="hover:bg-zinc-50/80">
                    <td class="whitespace-nowrap px-4 py-3 tabular-nums text-zinc-600">{{ $order->id }}</td>
                    <td class="max-w-[120px] truncate px-4 py-3 font-mono text-xs text-zinc-700" title="{{ $order->token }}">{{ $order->token }}</td>
                    <td class="whitespace-nowrap px-4 py-3">
                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-800">{{ \Illuminate\Support\Facades\Lang::has('admin.orders.status_labels.'.$order->status) ? __('admin.orders.status_labels.'.$order->status) : $order->status }}</span>
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-zinc-700">
                        @if ($order->user)
                            {{ $order->user->email }}
                        @else
                            <span class="text-zinc-400">{{ __('admin.orders.guest') }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ $order->items_count }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-zinc-600"><x-local-datetime :at="$order->placed_at"/></td>
                    <td class="whitespace-nowrap px-4 py-3 text-zinc-600"><x-local-datetime :at="$order->created_at"/></td>
                    <td class="whitespace-nowrap px-4 py-3 text-zinc-600"><x-local-datetime :at="$order->updated_at"/></td>
                    <td class="whitespace-nowrap px-4 py-3 space-x-2 text-right">
                        <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-amber-900 hover:underline">{{ __('admin.orders.view') }}</a>
                        <a href="{{ route('admin.orders.edit', $order) }}" class="font-medium text-zinc-700 hover:underline">{{ __('admin.orders.edit_link') }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-zinc-500">{{ __('admin.orders.empty') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $orders->links() }}
    </div>
@endsection
