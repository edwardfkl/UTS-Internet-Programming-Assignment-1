@extends('admin.layout')

@section('title', __('admin.promo_codes.create.meta_title'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.promo-codes.index') }}" class="text-sm font-medium text-amber-900 hover:underline">{{ __('admin.promo_codes.create.back') }}</a>
    </div>

    <h1 class="text-2xl font-semibold text-zinc-900">{{ __('admin.promo_codes.create.heading') }}</h1>

    <form method="post" action="{{ route('admin.promo-codes.store') }}" class="mt-8 max-w-xl">
        @csrf
        @include('admin.promo_codes._form', ['promo' => null, 'types' => $types])
        <div class="mt-6">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">
                {{ __('admin.common.create') }}
            </button>
        </div>
    </form>
@endsection
