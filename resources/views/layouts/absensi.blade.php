<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>{{ $title ?? 'WorkSync - Absensi' }}</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "outline": "#757682",
                        "on-primary-container": "#90a8ff",
                        "tertiary-container": "#003d88",
                        "primary-fixed-dim": "#b6c4ff",
                        "primary": "#00236f",
                        "on-primary": "#ffffff",
                        "inverse-primary": "#b6c4ff",
                        "on-secondary-container": "#54647a",
                        "tertiary-fixed-dim": "#adc6ff",
                        "on-tertiary-fixed-variant": "#004395",
                        "on-primary-fixed-variant": "#264191",
                        "on-error": "#ffffff",
                        "on-tertiary-container": "#82abff",
                        "on-secondary": "#ffffff",
                        "on-background": "#191c1e",
                        "tertiary": "#00285e",
                        "on-secondary-fixed-variant": "#38485d",
                        "error-container": "#ffdad6",
                        "surface-container-low": "#f2f4f6",
                        "inverse-on-surface": "#eff1f3",
                        "on-surface-variant": "#444651",
                        "surface-bright": "#f7f9fb",
                        "surface-dim": "#d8dadc",
                        "outline-variant": "#c5c5d3",
                        "primary-container": "#1e3a8a",
                        "surface-container": "#eceef0",
                        "on-tertiary": "#ffffff",
                        "error": "#ba1a1a",
                        "secondary-container": "#d0e1fb",
                        "on-tertiary-fixed": "#001a42",
                        "surface-container-high": "#e6e8ea",
                        "on-primary-fixed": "#00164e",
                        "on-secondary-fixed": "#0b1c30",
                        "surface-tint": "#4059aa",
                        "inverse-surface": "#2d3133",
                        "surface-container-lowest": "#ffffff",
                        "secondary-fixed": "#d3e4fe",
                        "surface": "#f7f9fb",
                        "background": "#f7f9fb",
                        "secondary": "#505f76",
                        "on-error-container": "#93000a",
                        "on-surface": "#191c1e",
                        "primary-fixed": "#dce1ff",
                        "surface-container-highest": "#e0e3e5",
                        "tertiary-fixed": "#d8e2ff",
                        "surface-variant": "#e0e3e5",
                        "secondary-fixed-dim": "#b7c8e1"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "base": "8px",
                        "stack-md": "12px",
                        "container-margin": "24px",
                        "stack-sm": "4px",
                        "stack-lg": "24px",
                        "desktop-max-width": "1280px",
                        "gutter": "16px"
                    },
                    "fontFamily": {
                        "headline-lg": ["Inter"],
                        "display-lg": ["Inter"],
                        "title-lg": ["Inter"],
                        "body-md": ["Inter"],
                        "label-md": ["Inter"],
                        "body-lg": ["Inter"],
                        "button": ["Inter"],
                        "headline-lg-mobile": ["Inter"],
                        "headline-md": ["Inter"]
                    },
                    "fontSize": {
                        "headline-lg": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                        "display-lg": ["48px", {"lineHeight": "56px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "title-lg": ["20px", {"lineHeight": "28px", "fontWeight": "600"}],
                        "body-md": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "label-md": ["12px", {"lineHeight": "16px", "letterSpacing": "0.05em", "fontWeight": "500"}],
                        "body-lg": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "button": ["14px", {"lineHeight": "20px", "fontWeight": "600"}],
                        "headline-lg-mobile": ["24px", {"lineHeight": "32px", "fontWeight": "600"}],
                        "headline-md": ["24px", {"lineHeight": "32px", "fontWeight": "600"}]
                    }
                },
            },
        }
    </script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #F8FAFC;
            min-height: max(884px, 100dvh);
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="{{ asset('assets/js/lib/iconify-icon.min.js') }}" data-navigate-once></script>
    @livewireStyles
</head>
<body class="bg-background text-on-background min-h-screen pb-32">

    @if (!request()->routeIs('absensi.login') && !request()->routeIs('absensi.verifikasi'))
    @persist('absensi-header')
    <!-- TopAppBar -->
    <header class="bg-surface dark:bg-surface-dim border-b border-outline-variant dark:border-outline w-full top-0 sticky z-50 shadow-sm">
        <div class="flex justify-between items-center px-container-margin py-stack-md w-full max-w-[1280px] mx-auto">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl overflow-hidden bg-surface-container border border-outline-variant flex items-center justify-center p-1">
                    <img alt="Brand Web Logo" class="w-full h-full object-contain" src="{{ asset($webSetting->logo ?? 'logo/logow.png') }}"/>
                </div>
                <h1 class="font-headline-md text-headline-md font-bold text-primary dark:text-primary-fixed-dim">{{ $webSetting->app_name ?? 'WorkSync' }}</h1>
            </div>
            <div class="flex items-center gap-2">
                @auth
                <span class="hidden md:inline font-body-md text-body-md text-secondary font-medium mr-2">{{ auth()->user()->name }}</span>
                @endauth
                <button class="material-symbols-outlined p-2 rounded-full text-on-surface-variant dark:text-outline-variant hover:bg-surface-container-low dark:hover:bg-surface-container-high transition-colors active:opacity-80">
                    notifications
                </button>
            </div>
        </div>
    </header>
    @endpersist

    <!-- Desktop Sidebar/Nav -->
    <div class="hidden md:flex fixed left-0 top-16 bottom-0 w-20 flex-col items-center py-8 gap-8 border-r border-outline-variant bg-surface z-40">
        <a href="{{ route('absensi.home') }}" wire:navigate class="{{ request()->routeIs('absensi.home') ? 'text-primary bg-primary-container' : 'text-secondary hover:text-primary' }} p-3 rounded-xl transition-colors" title="Dashboard">
            <span class="material-symbols-outlined" style="{{ request()->routeIs('absensi.home') ? "font-variation-settings: 'FILL' 1;" : '' }}">dashboard</span>
        </a>
        <a href="{{ route('absensi.clock.in') }}" wire:navigate class="{{ request()->routeIs('absensi.clock.in') ? 'text-primary bg-primary-container' : 'text-secondary hover:text-primary' }} p-3 rounded-xl transition-colors" title="Attendance">
            <span class="material-symbols-outlined" style="{{ request()->routeIs('absensi.clock.in') ? "font-variation-settings: 'FILL' 1;" : '' }}">schedule</span>
        </a>
        <a href="{{ route('absensi.history') }}" wire:navigate class="{{ request()->routeIs('absensi.history') ? 'text-primary bg-primary-container' : 'text-secondary hover:text-primary' }} p-3 rounded-xl transition-colors" title="History">
            <span class="material-symbols-outlined" style="{{ request()->routeIs('absensi.history') ? "font-variation-settings: 'FILL' 1;" : '' }}">history</span>
        </a>
        <a href="{{ route('absensi.profile') }}" wire:navigate class="{{ request()->routeIs('absensi.profile') ? 'text-primary bg-primary-container' : 'text-secondary hover:text-primary' }} mt-auto p-3 rounded-xl transition-colors" title="Profile">
            <span class="material-symbols-outlined" style="{{ request()->routeIs('absensi.profile') ? "font-variation-settings: 'FILL' 1;" : '' }}">person</span>
        </a>
    </div>
    @endif

    <!-- Main Content Wrapper -->
    <div class="{{ !request()->routeIs('absensi.login') ? 'md:pl-20' : '' }}">
        {{ $slot }}
    </div>

    @if (!request()->routeIs('absensi.login') && !request()->routeIs('absensi.verifikasi'))
    <!-- BottomNavBar (Mobile) -->
    <nav class="fixed bottom-0 left-0 right-0 z-50 flex justify-around items-center py-stack-sm px-gutter bg-surface dark:bg-surface-dim border-t border-outline-variant dark:border-outline shadow-sm md:hidden">
        <a href="{{ route('absensi.home') }}" wire:navigate class="flex flex-col items-center justify-center {{ request()->routeIs('absensi.home') ? 'bg-primary-container text-on-primary-container rounded-xl' : 'text-secondary' }} px-4 py-1 active:scale-95 transition-all duration-150">
            <span class="material-symbols-outlined" style="{{ request()->routeIs('absensi.home') ? "font-variation-settings: 'FILL' 1;" : '' }}">dashboard</span>
            <span class="font-label-md text-label-md mt-1">Dashboard</span>
        </a>
        <a href="{{ route('absensi.clock.in') }}" wire:navigate class="flex flex-col items-center justify-center {{ request()->routeIs('absensi.clock.in') ? 'bg-primary-container text-on-primary-container rounded-xl' : 'text-secondary' }} px-4 py-1 active:scale-95 transition-all duration-150">
            <span class="material-symbols-outlined" style="{{ request()->routeIs('absensi.clock.in') ? "font-variation-settings: 'FILL' 1;" : '' }}">schedule</span>
            <span class="font-label-md text-label-md mt-1">Attendance</span>
        </a>
        <a href="{{ route('absensi.history') }}" wire:navigate class="flex flex-col items-center justify-center {{ request()->routeIs('absensi.history') ? 'bg-primary-container text-on-primary-container rounded-xl' : 'text-secondary' }} px-4 py-1 active:scale-95 transition-all duration-150">
            <span class="material-symbols-outlined" style="{{ request()->routeIs('absensi.history') ? "font-variation-settings: 'FILL' 1;" : '' }}">history</span>
            <span class="font-label-md text-label-md mt-1">History</span>
        </a>
        <a href="{{ route('absensi.profile') }}" wire:navigate class="flex flex-col items-center justify-center {{ request()->routeIs('absensi.profile') ? 'bg-primary-container text-on-primary-container rounded-xl' : 'text-secondary' }} px-4 py-1 active:scale-95 transition-all duration-150">
            <span class="material-symbols-outlined" style="{{ request()->routeIs('absensi.profile') ? "font-variation-settings: 'FILL' 1;" : '' }}">person</span>
            <span class="font-label-md text-label-md mt-1">Profile</span>
        </a>
    </nav>
    @endif

    @livewireScripts
</body>
</html>
