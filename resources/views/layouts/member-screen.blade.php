<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? ($webSetting->app_name ?? 'Temuan Space') }}</title>
    <link rel="icon" type="image/png" href="{{ asset($webSetting->icon ?? 'logo/logow.png') }}" sizes="16x16">
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
