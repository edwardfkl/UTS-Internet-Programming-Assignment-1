@extends('admin.layout')

@section('title', __('admin.products.title'))

@php
    $statusBadge = [
        \App\Models\Product::STATUS_ACTIVE => 'bg-emerald-100 text-emerald-900',
        \App\Models\Product::STATUS_INACTIVE => 'bg-zinc-200 text-zinc-700',
        \App\Models\Product::STATUS_DRAFT => 'bg-amber-100 text-amber-900',
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">{{ __('admin.products.heading') }}</h1>
            <p class="mt-1 text-sm text-zinc-600">{{ __('admin.products.intro') }}</p>
        </div>
        <a href="{{ route('admin.products.create') }}"
           class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">{{ __('admin.products.add') }}</a>
    </div>

    <form method="get" action="{{ route('admin.products.index') }}" data-admin-live-search class="mt-6 flex flex-wrap items-end gap-3">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="dir" value="{{ $dir }}">
        <div class="min-w-[180px]">
            <label for="products-status" class="mb-1 block text-xs font-medium text-zinc-600">{{ __('admin.products.filter_status') }}</label>
            <select id="products-status" name="status"
                    onchange="this.form.submit()"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-amber-900 focus:outline-none focus:ring-1 focus:ring-amber-900">
                <option value="" @selected($statusFilter === null)>{{ __('admin.products.filter_status_all') }}</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption }}" @selected($statusFilter === $statusOption)>
                        {{ __('admin.products.status_labels.'.$statusOption) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="min-w-[200px] flex-1">
            <label for="products-q" class="mb-1 block text-xs font-medium text-zinc-600">{{ __('admin.common.search') }}</label>
            <input id="products-q" type="search" name="q" value="{{ $q ?? '' }}"
                   placeholder="{{ __('admin.products.search_placeholder') }}"
                   class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-amber-900 focus:outline-none focus:ring-1 focus:ring-amber-900">
        </div>
    </form>

    <form method="post" action="{{ route('admin.products.bulk') }}" data-bulk-form class="mt-4">
        @csrf
        <input type="hidden" name="q" value="{{ $q ?? '' }}">
        <input type="hidden" name="status" value="{{ $statusFilter ?? '' }}">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="dir" value="{{ $dir }}">
        <input type="hidden" name="page" value="{{ $products->currentPage() }}">

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wide text-zinc-600">
                <tr>
                    <th class="w-10 px-3 py-3 text-center">
                        <input type="checkbox" data-bulk-select-all
                               class="rounded border-zinc-300 text-amber-900">
                    </th>
                    <x-admin.sort-th :label="__('admin.products.col_id')" column="id" :sort="$sort" :dir="$dir" route="admin.products.index" :preserve-query="['q', 'status']"/>
                    <x-admin.sort-th :label="__('admin.products.col_name')" column="name" :sort="$sort" :dir="$dir" route="admin.products.index" :preserve-query="['q', 'status']"/>
                    <x-admin.sort-th :label="__('admin.products.col_status')" column="status" :sort="$sort" :dir="$dir" route="admin.products.index" :preserve-query="['q', 'status']"/>
                    <x-admin.sort-th :label="__('admin.products.col_price')" column="price" :sort="$sort" :dir="$dir" route="admin.products.index" :preserve-query="['q', 'status']"/>
                    <x-admin.sort-th :label="__('admin.products.col_stock')" column="stock" :sort="$sort" :dir="$dir" route="admin.products.index" :preserve-query="['q', 'status']"/>
                    <x-admin.sort-th :label="__('admin.products.col_created')" column="created_at" :sort="$sort" :dir="$dir" route="admin.products.index" :preserve-query="['q', 'status']"/>
                    <x-admin.sort-th :label="__('admin.products.col_updated')" column="updated_at" :sort="$sort" :dir="$dir" route="admin.products.index" :preserve-query="['q', 'status']"/>
                    <th class="px-4 py-3 text-right">{{ __('admin.products.col_actions') }}</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                @forelse ($products as $product)
                    <tr class="hover:bg-zinc-50/80">
                        <td class="px-3 py-3 text-center">
                            <input type="checkbox" name="ids[]" value="{{ $product->id }}" data-bulk-id
                                   class="rounded border-zinc-300 text-amber-900">
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-zinc-600">{{ $product->id }}</td>
                        <td class="px-4 py-3 font-medium text-zinc-900">{{ $product->name }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusBadge[$product->status] ?? 'bg-zinc-100 text-zinc-800' }}">
                                {{ __('admin.products.status_labels.'.$product->status) }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums"><x-aud-money :amount="$product->price" /></td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums">{{ $product->stock }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-600"><x-local-datetime :at="$product->created_at"/></td>
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-600"><x-local-datetime :at="$product->updated_at"/></td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-amber-900 hover:underline">{{ __('admin.products.edit_link') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-zinc-500">{{ __('admin.products.empty') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @include('admin.partials.bulk-toolbar', [
            'bulkRoute' => 'admin.products.bulk',
            'bulkStatuses' => $statuses,
            'bulkResourceKey' => 'admin.products',
        ])
    </form>

    <div class="mt-6">
        {{ $products->links() }}
    </div>

    @include('admin.partials.bulk-script')
@endsection
