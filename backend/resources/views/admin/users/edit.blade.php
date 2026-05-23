@extends('admin.layout')

@section('title', __('admin.users.edit.meta_title'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-amber-900 hover:underline">{{ __('admin.users.edit.back') }}</a>
    </div>

    <h1 class="text-2xl font-semibold text-zinc-900">{{ __('admin.users.edit.heading', ['id' => $user->id]) }}</h1>

    <form method="post" action="{{ route('admin.users.update', $user) }}" class="mt-8 max-w-xl space-y-4">
        @csrf
        @method('put')

        @include('admin.users._form', ['user' => $user, 'statuses' => $statuses])

        <div class="flex flex-wrap gap-3 pt-4">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">
                {{ __('admin.common.save') }}
            </button>
        </div>
    </form>

    @if (! auth()->user()->is($user))
        <form method="post" action="{{ route('admin.users.destroy', $user) }}" class="mt-12 max-w-xl border-t border-zinc-200 pt-8"
              onsubmit="return confirm({{ json_encode(__('admin.users.edit.delete_confirm')) }});">
            @csrf
            @method('delete')
            <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-900 hover:bg-red-100">
                {{ __('admin.users.edit.delete') }}
            </button>
        </form>
    @endif
@endsection
