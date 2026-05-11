@extends('admin.layout')

@section('title', __('admin.promo_codes.edit.meta_title'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.promo-codes.index') }}" class="text-sm font-medium text-amber-900 hover:underline">{{ __('admin.promo_codes.edit.back') }}</a>
    </div>

    <h1 class="text-2xl font-semibold text-zinc-900">{{ __('admin.promo_codes.edit.heading', ['code' => $promo->code]) }}</h1>

    <form method="post" action="{{ route('admin.promo-codes.update', $promo) }}" class="mt-8 max-w-xl">
        @csrf
        @method('put')
        @include('admin.promo_codes._form', ['promo' => $promo, 'types' => $types])
        <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">
                {{ __('admin.common.save') }}
            </button>
        </div>
    </form>

    <form method="post" action="{{ route('admin.promo-codes.destroy', $promo) }}" class="mt-12 max-w-xl border-t border-zinc-200 pt-8"
          onsubmit="return confirm({{ json_encode(__('admin.promo_codes.edit.delete_confirm')) }});">
        @csrf
        @method('delete')
        <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-900 hover:bg-red-100">
            {{ __('admin.promo_codes.edit.delete') }}
        </button>
    </form>
@endsection
