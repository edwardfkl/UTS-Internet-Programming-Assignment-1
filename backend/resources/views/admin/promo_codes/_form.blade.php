@php
    $promo = $promo ?? null;
    $types = $types ?? \App\Models\PromoCode::TYPES;
@endphp

<div class="space-y-4">
    <div>
        <label for="code" class="block text-sm font-medium text-zinc-700">{{ __('admin.promo_codes.form.code') }}</label>
        <input id="code" name="code" type="text" required
               value="{{ old('code', $promo?->code) }}"
               placeholder="WELCOME10"
               class="mt-1 w-full max-w-xs rounded-lg border border-zinc-300 px-3 py-2 font-mono text-sm shadow-sm uppercase">
        <p class="mt-1 text-xs text-zinc-500">{{ __('admin.promo_codes.form.code_hint') }}</p>
    </div>
    <div>
        <label for="label" class="block text-sm font-medium text-zinc-700">{{ __('admin.promo_codes.form.label') }}</label>
        <input id="label" name="label" type="text"
               value="{{ old('label', $promo?->label) }}"
               class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="type" class="block text-sm font-medium text-zinc-700">{{ __('admin.promo_codes.form.type') }}</label>
            <select id="type" name="type" required
                    class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                @foreach ($types as $typeOption)
                    <option value="{{ $typeOption }}" @selected(old('type', $promo?->type ?? \App\Models\PromoCode::TYPE_FIXED) === $typeOption)>
                        {{ __('admin.promo_codes.type_labels.'.$typeOption) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="amount" class="block text-sm font-medium text-zinc-700">{{ __('admin.promo_codes.form.amount') }}</label>
            <input id="amount" name="amount" type="number" step="0.01" min="0" required
                   value="{{ old('amount', $promo?->amount) }}"
                   class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm tabular-nums">
            <p class="mt-1 text-xs text-zinc-500">{{ __('admin.promo_codes.form.amount_hint') }}</p>
        </div>
    </div>
    <div>
        <label for="min_subtotal" class="block text-sm font-medium text-zinc-700">{{ __('admin.promo_codes.form.min_subtotal') }}</label>
        <input id="min_subtotal" name="min_subtotal" type="number" step="0.01" min="0"
               value="{{ old('min_subtotal', $promo?->min_subtotal) }}"
               class="mt-1 w-full max-w-xs rounded-lg border border-zinc-300 px-3 py-2 shadow-sm tabular-nums">
        <p class="mt-1 text-xs text-zinc-500">{{ __('admin.promo_codes.form.min_subtotal_hint') }}</p>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="starts_at" class="block text-sm font-medium text-zinc-700">{{ __('admin.promo_codes.form.starts_at') }}</label>
            <input id="starts_at" name="starts_at" type="datetime-local"
                   value="{{ old('starts_at', $promo?->starts_at?->format('Y-m-d\TH:i')) }}"
                   class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
        </div>
        <div>
            <label for="ends_at" class="block text-sm font-medium text-zinc-700">{{ __('admin.promo_codes.form.ends_at') }}</label>
            <input id="ends_at" name="ends_at" type="datetime-local"
                   value="{{ old('ends_at', $promo?->ends_at?->format('Y-m-d\TH:i')) }}"
                   class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
        </div>
    </div>
    <div class="flex items-center gap-2">
        <input id="is_active" name="is_active" type="checkbox" value="1"
               class="rounded border-zinc-300 text-amber-900"
               @checked(old('is_active', $promo?->is_active ?? true))>
        <label for="is_active" class="text-sm font-medium text-zinc-700">{{ __('admin.promo_codes.form.is_active') }}</label>
    </div>
</div>
