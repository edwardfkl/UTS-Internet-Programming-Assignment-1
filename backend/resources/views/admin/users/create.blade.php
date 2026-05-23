@extends('admin.layout')

@section('title', __('admin.users.create.meta_title'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-amber-900 hover:underline">{{ __('admin.users.create.back') }}</a>
    </div>

    <h1 class="text-2xl font-semibold text-zinc-900">{{ __('admin.users.create.heading') }}</h1>

    <form method="post" action="{{ route('admin.users.store') }}" class="mt-8 max-w-xl space-y-4">
        @csrf
        @include('admin.users._form', ['user' => null, 'statuses' => $statuses])
        <div class="flex flex-wrap gap-3 pt-4">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">
                {{ __('admin.common.create') }}
            </button>
        </div>
    </form>
@endsection
