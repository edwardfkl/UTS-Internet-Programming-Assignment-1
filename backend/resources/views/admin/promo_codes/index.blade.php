@extends('admin.layout')

@section('title', __('admin.promo_codes.title'))

@php
    $bulkStatusOptions = ['active', 'inactive'];
@endphp

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">{{ __('admin.promo_codes.heading') }}</h1>
            <p class="mt-1 text-sm text-zinc-600">{{ __('admin.promo_codes.intro') }}</p>
        </div>
        <a href="{{ route('admin.promo-codes.create') }}"
           class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">{{ __('admin.promo_codes.add') }}</a>
    </div>

    <form method="get" action="{{ route('admin.promo-codes.index') }}" data-admin-live-search class="mt-6 flex flex-wrap items-end gap-3">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="dir" value="{{ $dir }}">
        <div class="min-w-[180px]">
            <label for="promo-status" class="mb-1 block text-xs font-medium text-zinc-600">{{ __('admin.promo_codes.filter_status') }}</label>
            <select id="promo-status" name="status"
                    onchange="this.form.submit()"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-amber-900 focus:outline-none focus:ring-1 focus:ring-amber-900">
                <option value="" @selected($statusFilter === null)>{{ __('admin.promo_codes.filter_status_all') }}</option>
                <option value="active" @selected($statusFilter === 'active')>{{ __('admin.promo_codes.status_labels.active') }}</option>
                <option value="inactive" @selected($statusFilter === 'inactive')>{{ __('admin.promo_codes.status_labels.inactive') }}</option>
            </select>
        </div>
        <div class="min-w-[200px] flex-1">
            <label for="promo-q" class="mb-1 block text-xs font-medium text-zinc-600">{{ __('admin.common.search') }}</label>
            <input id="promo-q" type="search" name="q" value="{{ $q ?? '' }}"
                   placeholder="{{ __('admin.promo_codes.search_placeholder') }}"
                   class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-amber-900 focus:outline-none focus:ring-1 focus:ring-amber-900">
        </div>
    </form>

    <form method="post" action="{{ route('admin.promo-codes.bulk') }}" data-bulk-form class="mt-4">
        @csrf
        <input type="hidden" name="q" value="{{ $q ?? '' }}">
        <input type="hidden" name="status" value="{{ $statusFilter ?? '' }}">
        <input type="hidden" name="sort" value="{{ $sort }}">
        <input type="hidden" name="dir" value="{{ $dir }}">
        <input type="hidden" name="page" value="{{ $codes->currentPage() }}">

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-zinc-200 text-sm">
                <thead class="bg-zinc-50 text-left text-xs font-medium uppercase tracking-wide text-zinc-600">
                <tr>
                    <th class="w-10 px-3 py-3 text-center">
                        <input type="checkbox" data-bulk-select-all
                               class="rounded border-zinc-300 text-amber-900">
                    </th>
                    <x-admin.sort-th :label="__('admin.promo_codes.col_id')" column="id" :sort="$sort" :dir="$dir" route="admin.promo-codes.index" :preserve-query="['q', 'status']"/>
                    <x-admin.sort-th :label="__('admin.promo_codes.col_code')" column="code" :sort="$sort" :dir="$dir" route="admin.promo-codes.index" :preserve-query="['q', 'status']"/>
                    <th class="px-4 py-3">{{ __('admin.promo_codes.col_label') }}</th>
                    <x-admin.sort-th :label="__('admin.promo_codes.col_type')" column="type" :sort="$sort" :dir="$dir" route="admin.promo-codes.index" :preserve-query="['q', 'status']"/>
                    <x-admin.sort-th :label="__('admin.promo_codes.col_amount')" column="amount" :sort="$sort" :dir="$dir" route="admin.promo-codes.index" :preserve-query="['q', 'status']"/>
                    <th class="px-4 py-3">{{ __('admin.promo_codes.col_window') }}</th>
                    <x-admin.sort-th :label="__('admin.promo_codes.col_status')" column="is_active" :sort="$sort" :dir="$dir" route="admin.promo-codes.index" :preserve-query="['q', 'status']"/>
                    <th class="px-4 py-3 text-right">{{ __('admin.promo_codes.col_actions') }}</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                @forelse ($codes as $promo)
                    <tr class="hover:bg-zinc-50/80">
                        <td class="px-3 py-3 text-center">
                            <input type="checkbox" name="ids[]" value="{{ $promo->id }}" data-bulk-id
                                   class="rounded border-zinc-300 text-amber-900">
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-zinc-600">{{ $promo->id }}</td>
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-zinc-900">{{ $promo->code }}</td>
                        <td class="px-4 py-3 text-zinc-700">{{ $promo->label ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-700">
                            {{ __('admin.promo_codes.type_labels.'.$promo->type) }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-zinc-800">
                            @if ($promo->type === \App\Models\PromoCode::TYPE_PERCENT)
                                {{ rtrim(rtrim(number_format((float) $promo->amount, 2), '0'), '.') }}%
                            @else
                                {{ number_format((float) $promo->amount, 2) }}
                            @endif
                            @if ($promo->min_subtotal !== null)
                                <div class="text-xs text-zinc-500">
                                    {{ __('admin.promo_codes.min_short', ['amount' => number_format((float) $promo->min_subtotal, 2)]) }}
                                </div>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-600">
                            @if ($promo->starts_at || $promo->ends_at)
                                <div>
                                    @if ($promo->starts_at)
                                        <x-local-datetime :at="$promo->starts_at"/>
                                    @else
                                        —
                                    @endif
                                </div>
                                <div class="text-zinc-400">↓</div>
                                <div>
                                    @if ($promo->ends_at)
                                        <x-local-datetime :at="$promo->ends_at"/>
                                    @else
                                        —
                                    @endif
                                </div>
                            @else
                                <span class="text-zinc-400">{{ __('admin.promo_codes.always') }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if ($promo->is_active)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900">{{ __('admin.promo_codes.status_labels.active') }}</span>
                            @else
                                <span class="rounded-full bg-zinc-200 px-2 py-0.5 text-xs font-medium text-zinc-700">{{ __('admin.promo_codes.status_labels.inactive') }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('admin.promo-codes.edit', $promo) }}" class="font-medium text-amber-900 hover:underline">{{ __('admin.promo_codes.edit_link') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-zinc-500">{{ __('admin.promo_codes.empty') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @include('admin.partials.bulk-toolbar', [
            'bulkRoute' => 'admin.promo-codes.bulk',
            'bulkStatuses' => $bulkStatusOptions,
            'bulkResourceKey' => 'admin.promo_codes',
        ])
    </form>

    <div class="mt-6">
        {{ $codes->links() }}
    </div>

    @include('admin.partials.bulk-script')
@endsection
