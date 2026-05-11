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

        <div>
            <label for="name" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.name') }}</label>
            <input id="name" name="name" type="text" required value="{{ old('name', $user->name) }}"
                   class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
        </div>
        <div>
            <label for="email" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.email') }}</label>
            <input id="email" name="email" type="email" required value="{{ old('email', $user->email) }}"
                   class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
        </div>
        <div>
            <label for="avatar_url" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.avatar_url') }}</label>
            <input id="avatar_url" name="avatar_url" type="url" placeholder="{{ __('admin.users.edit.avatar_placeholder') }}"
                   value="{{ old('avatar_url', $user->avatar_url) }}"
                   class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
        </div>
        <div class="flex items-center gap-2">
            <input id="is_admin" name="is_admin" type="checkbox" value="1"
                   class="rounded border-zinc-300 text-amber-900" @checked(old('is_admin', $user->is_admin))>
            <label for="is_admin" class="text-sm font-medium text-zinc-700">{{ __('admin.users.edit.admin_access') }}</label>
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.status') }}</label>
            <select id="status" name="status" required
                    class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption }}" @selected(old('status', $user->status) === $statusOption)>
                        {{ __('admin.users.status_labels.'.$statusOption) }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-zinc-500">{{ __('admin.users.edit.status_hint') }}</p>
        </div>
        <div>
            <label for="phone" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.phone') }}</label>
            <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}"
                   class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
        </div>

        <fieldset class="rounded-lg border border-zinc-200 p-4">
            <legend class="px-1 text-sm font-medium text-zinc-800">{{ __('admin.users.edit.shipping_legend') }}</legend>
            <div class="mt-2 space-y-4">
                <div>
                    <label for="shipping_recipient_name" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.recipient') }}</label>
                    <input id="shipping_recipient_name" name="shipping_recipient_name" type="text"
                           value="{{ old('shipping_recipient_name', $user->shipping_recipient_name) }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div>
                    <label for="shipping_line1" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.address_line1') }}</label>
                    <input id="shipping_line1" name="shipping_line1" type="text"
                           value="{{ old('shipping_line1', $user->shipping_line1) }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div>
                    <label for="shipping_line2" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.address_line2') }}</label>
                    <input id="shipping_line2" name="shipping_line2" type="text"
                           value="{{ old('shipping_line2', $user->shipping_line2) }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="shipping_city" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.city') }}</label>
                        <input id="shipping_city" name="shipping_city" type="text"
                               value="{{ old('shipping_city', $user->shipping_city) }}"
                               class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                    </div>
                    <div>
                        <label for="shipping_state" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.state') }}</label>
                        <input id="shipping_state" name="shipping_state" type="text"
                               value="{{ old('shipping_state', $user->shipping_state) }}"
                               class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                    </div>
                    <div>
                        <label for="shipping_postcode" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.postcode') }}</label>
                        <input id="shipping_postcode" name="shipping_postcode" type="text"
                               value="{{ old('shipping_postcode', $user->shipping_postcode) }}"
                               class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                    </div>
                    <div>
                        <label for="shipping_country" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.country') }}</label>
                        <input id="shipping_country" name="shipping_country" type="text"
                               value="{{ old('shipping_country', $user->shipping_country) }}"
                               class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
                    </div>
                </div>
            </div>
        </fieldset>

        <div>
            <label for="password" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.new_password') }}</label>
            <input id="password" name="password" type="password" autocomplete="new-password"
                   class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
        </div>
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-zinc-700">{{ __('admin.users.edit.confirm_password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                   class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 shadow-sm">
        </div>

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
