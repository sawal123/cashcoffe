<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @php
        $siteName = trim((string) ($webSetting->app_name ?? 'Temuan Space')) ?: 'Temuan Space';
        $seoTitle = trim((string) ($webSetting->seo_title ?? '')) ?: $siteName;
        $seoDescription = trim((string) ($webSetting->seo_description ?? '')) ?: $siteName . ' - portal member.';
        $siteLogo = $webSetting->logo ?? 'logo/logow.png';
        $siteIcon = $webSetting->icon ?? $siteLogo;
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta property="og:title" content="{{ $title ?? $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:image" content="{{ asset($siteLogo) }}">
    <link rel="icon" type="image/png" href="{{ asset($siteIcon) }}" sizes="16x16">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="m-0 h-screen h-[100dvh] overflow-hidden bg-white font-sans antialiased">
    {{ $slot }}

    @livewireScripts
</body>

</html>
