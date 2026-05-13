<!DOCTYPE html>
<html lang="{{ $adminHtmlLang ?? 'en' }}" data-intl-locale="{{ $adminIntlLocale ?? 'en-AU' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('admin.title_default')) — {{ __('admin.store_name') }}</title>
    @include('admin.partials.styles')
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
    <header class="border-b border-zinc-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-4 py-4">
            <div class="flex flex-wrap items-center gap-6">
                <a href="{{ route('admin.dashboard') }}" class="font-semibold text-zinc-900">{{ __('admin.nav.brand') }}</a>
                <nav class="flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('admin.dashboard') }}" class="text-zinc-600 hover:text-zinc-900 @if(request()->routeIs('admin.dashboard')) font-medium text-zinc-900 @endif">{{ __('admin.nav.dashboard') }}</a>
                    <a href="{{ route('admin.users.index') }}" class="text-zinc-600 hover:text-zinc-900 @if(request()->routeIs('admin.users.*')) font-medium text-zinc-900 @endif">{{ __('admin.nav.users') }}</a>
                    <a href="{{ route('admin.products.index') }}" class="text-zinc-600 hover:text-zinc-900 @if(request()->routeIs('admin.products.*')) font-medium text-zinc-900 @endif">{{ __('admin.nav.products') }}</a>
                    <a href="{{ route('admin.orders.index') }}" class="text-zinc-600 hover:text-zinc-900 @if(request()->routeIs('admin.orders.*')) font-medium text-zinc-900 @endif">{{ __('admin.nav.orders') }}</a>
                    <a href="{{ route('admin.promo-codes.index') }}" class="text-zinc-600 hover:text-zinc-900 @if(request()->routeIs('admin.promo-codes.*')) font-medium text-zinc-900 @endif">{{ __('admin.nav.promo_codes') }}</a>
                </nav>
            </div>
            <div class="flex items-center gap-3">
                @include('admin.partials.language-menu')
                <form method="post" action="{{ route('admin.logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-amber-900 hover:underline">{{ __('admin.nav.logout') }}</button>
                </form>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('success'))
            <p class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</p>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
    @include('admin.partials.local-datetime-script')
    @include('admin.partials.live-search-script')
</body>
</html>
