@extends('admin.layout')

@section('title', __('admin.order_items.edit.meta_title', ['id' => $orderItem->id]))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.orders.show', $orderItem->order) }}" class="text-sm font-medium text-amber-900 hover:underline">{{ __('admin.order_items.edit.back', ['order_id' => $orderItem->order_id]) }}</a>
    </div>

    <h1 class="text-2xl font-semibold text-zinc-900">{{ __('admin.order_items.edit.heading', ['id' => $orderItem->id]) }}</h1>

    <form method="post" action="{{ route('admin.order-items.update', $orderItem) }}" class="mt-8 max-w-xl space-y-4">
        @csrf
        @method('put')

        <div>
            <label for="product_id" class="block text-sm font-medium text-zinc-700">{{ __('admin.order_items.edit.product') }}</label>
            <select id="product_id" name="product_id" required class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                @foreach ($products as $p)
                    <option value="{{ $p->id }}" @selected((int) old('product_id', $orderItem->product_id) === $p->id)>
                        #{{ $p->id }} — {{ $p->name }} ({{ \App\Support\Money::formatAud($p->price) }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="quantity" class="block text-sm font-medium text-zinc-700">{{ __('admin.order_items.edit.quantity') }}</label>
            <input id="quantity" name="quantity" type="number" min="1" required
                   value="{{ old('quantity', $orderItem->quantity) }}"
                   class="mt-1 w-full max-w-xs rounded-lg border border-zinc-300 px-3 py-2 shadow-sm tabular-nums">
        </div>
        <div>
            <label for="unit_price" class="block text-sm font-medium text-zinc-700">{{ __('admin.order_items.edit.unit_price') }}</label>
            <input id="unit_price" name="unit_price" type="number" step="0.01" min="0" required
                   value="{{ old('unit_price', $orderItem->unit_price) }}"
                   class="mt-1 w-full max-w-xs rounded-lg border border-zinc-300 px-3 py-2 shadow-sm tabular-nums">
        </div>

        <div class="flex flex-wrap gap-3 pt-4">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">{{ __('admin.common.save') }}</button>
        </div>
    </form>

    <form method="post" action="{{ route('admin.order-items.destroy', $orderItem) }}" class="mt-12 border-t border-zinc-200 pt-8"
          onsubmit="return confirm({{ json_encode(__('admin.order_items.edit.delete_confirm')) }});">
        @csrf
        @method('delete')
        <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-900 hover:bg-red-100">
            {{ __('admin.order_items.edit.delete') }}
        </button>
    </form>
@endsection
