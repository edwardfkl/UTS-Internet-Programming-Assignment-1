@php
    $product = $product ?? null;
    $statuses = $statuses ?? \App\Models\Product::STATUSES;
@endphp

<div class="space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-zinc-700">{{ __('admin.products.form.name') }}</label>
        <input id="name" name="name" type="text" required
               value="{{ old('name', $product?->name) }}"
               class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
    </div>
    <div>
        <label for="status" class="block text-sm font-medium text-zinc-700">{{ __('admin.products.form.status') }}</label>
        <select id="status" name="status" required
                class="mt-1 w-full max-w-xs rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption }}" @selected(old('status', $product?->status ?? \App\Models\Product::STATUS_ACTIVE) === $statusOption)>
                    {{ __('admin.products.status_labels.'.$statusOption) }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-zinc-500">{{ __('admin.products.form.status_hint') }}</p>
    </div>
    <div>
        <label for="description" class="block text-sm font-medium text-zinc-700">{{ __('admin.products.form.description') }}</label>
        <textarea id="description" name="description" rows="4"
                  class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">{{ old('description', $product?->description) }}</textarea>
    </div>
    <div>
        <label for="price" class="block text-sm font-medium text-zinc-700">{{ __('admin.products.form.price') }}</label>
        <input id="price" name="price" type="number" step="0.01" min="0" required
               value="{{ old('price', $product?->price) }}"
               class="mt-1 w-full max-w-xs rounded-lg border border-zinc-300 px-3 py-2 shadow-sm tabular-nums">
    </div>
    <div>
        <label for="stock" class="block text-sm font-medium text-zinc-700">{{ __('admin.products.form.stock') }}</label>
        <input id="stock" name="stock" type="number" min="0" required
               value="{{ old('stock', $product?->stock ?? 0) }}"
               class="mt-1 w-full max-w-xs rounded-lg border border-zinc-300 px-3 py-2 shadow-sm tabular-nums">
    </div>
    <div>
        <label for="image_url" class="block text-sm font-medium text-zinc-700">{{ __('admin.products.form.image_url') }}</label>
        <input id="image_url" name="image_url" type="url" placeholder="{{ __('admin.products.form.url_placeholder') }}"
               value="{{ old('image_url', $product?->image_url) }}"
               class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
    </div>
</div>
