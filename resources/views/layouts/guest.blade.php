<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @php
        $siteName = trim((string) ($webSetting->app_name ?? 'WorkSync')) ?: 'WorkSync';
        $seoTitle = trim((string) ($webSetting->seo_title ?? '')) ?: $siteName;
        $seoDescription = trim((string) ($webSetting->seo_description ?? '')) ?: $siteName . ' - sistem kasir dan operasional bisnis.';
        $siteLogo = $webSetting->logo ?? 'logo/logow.png';
        $siteIcon = $webSetting->icon ?? $siteLogo;
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:image" content="{{ asset($siteLogo) }}">
    <link rel="icon" type="image/png" href="{{ asset($siteIcon) }}" sizes="16x16">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#18181b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link class="apple-touch-icon" rel="apple-touch-icon" href="{{ asset($siteIcon) }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-gray-900 antialiased">
    <div class="auth-shell">
        <div class="auth-logo-wrap">
            {{-- <a href="/" wire:navigate>
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a> --}}
            <img src="{{ asset($siteLogo) }}" alt="site logo" class="auth-logo light-logo">
        </div>

        <div class="auth-card">
            {{ $slot }}
        </div>
    </div>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .catch(err => console.error('Service Worker registration failed:', err));
            });
        }
    </script>
</body>
</html>
