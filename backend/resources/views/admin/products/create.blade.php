@extends('admin.layout')

@section('title', __('admin.products.create.meta_title'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.products.index') }}" class="text-sm font-medium text-amber-900 hover:underline">{{ __('admin.products.create.back') }}</a>
    </div>

    <h1 class="text-2xl font-semibold text-zinc-900">{{ __('admin.products.create.heading') }}</h1>

    <form method="post" action="{{ route('admin.products.store') }}" class="mt-8 max-w-xl">
        @csrf
        @include('admin.products._form', ['product' => null, 'statuses' => $statuses])
        <div class="mt-6">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">
                {{ __('admin.common.create') }}
            </button>
        </div>
    </form>
@endsection
