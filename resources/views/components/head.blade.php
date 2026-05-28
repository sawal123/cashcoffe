<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $webSetting->app_name ?? 'WorkSync' }}</title>
    <link rel="icon" type="image/png" href="{{ asset($webSetting->icon ?? 'logo/logow.png') }}" sizes="16x16">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#18181b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="{{ asset('logo/logo.png') }}">
    <script data-navigate-once>
        (function () {
            const savedTheme = localStorage.getItem('color-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', savedTheme === 'dark' || (!savedTheme && prefersDark));
        })();
    </script>
    <!-- google fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <!-- Material Symbols -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <!-- remix icon font css  -->
    <link rel="stylesheet" href="{{ asset('assets/css/remixicon.css') }}">
    <!-- Data Table css -->
    <link rel="stylesheet" href="{{ asset('assets/css/lib/dataTables.min.css') }}">
    <!-- Text Editor css -->
    <link rel="stylesheet" href="{{ asset('assets/css/lib/editor-katex.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/editor.atom-one-dark.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/editor.quill.snow.css') }}">
    <!-- Date picker css -->
    <link rel="stylesheet" href="{{ asset('assets/css/lib/flatpickr.min.css') }}">
    <!-- Calendar css -->
    <link rel="stylesheet" href="{{ asset('assets/css/lib/full-calendar.css') }}">
    <!-- Vector Map css -->
    <link rel="stylesheet" href="{{ asset('assets/css/lib/jquery-jvectormap-2.0.5.css') }}">
    <!-- Popup css -->
    <link rel="stylesheet" href="{{ asset('assets/css/lib/magnific-popup.css') }}">
    <!-- Slick Slider css -->
    <link rel="stylesheet" href="{{ asset('assets/css/lib/slick.css') }}">
    <!-- prism css -->
    <link rel="stylesheet" href="{{ asset('assets/css/lib/prism.css') }}">
    <!-- file upload css -->
    <link rel="stylesheet" href="{{ asset('assets/css/lib/file-upload.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/lib/audioplayer.css') }}">
    <!-- main css -->
    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js" data-navigate-once></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- TomSelect CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">

    <!-- TomSelect JS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js" data-navigate-once></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />

    <script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js" data-navigate-once></script>



    <style>
        .ts-control {
            width: 100% !important;
            padding: 0.5rem 0.75rem !important;
            border-radius: 0.5rem !important;
            border: 1px solid #d1d5db !important;
            background-color: #ffffff !important;
            color: #1f2937 !important;
            font-size: 14px !important;
            font-weight: 500;
            height: 42px !important;
            display: flex;
            align-items: center;
        }

        /* Fokus ala Tailwind */
        .ts-control:focus,
        .ts-control.dropdown-active {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3) !important;
        }

        /* Placeholder */
        .ts-control .ts-placeholder {
            color: #9ca3af !important;
        }

        /* DARK MODE */
        .dark .ts-control {
            background-color: #1f2937 !important;
            border-color: #4b5563 !important;
            color: #e5e7eb !important;
        }

        /* ======================= */
        /*      DROPDOWN BOX       */
        /* ======================= */

        .ts-dropdown {
            background-color: #ffffff !important;
            border: 1px solid #d1d5db !important;
            color: #1f2937 !important;
            border-radius: 0.5rem !important;
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
            max-height: 220px !important;
            overflow-y: auto !important;
        }

        /* DARK MODE DROPDOWN */
        .dark .ts-dropdown {
            background-color: #1f2937 !important;
            border-color: #4b5563 !important;
            color: #e5e7eb !important;
        }

        /* ======================= */
        /*       ITEM STYLE        */
        /* ======================= */

        .ts-dropdown .ts-option {
            padding: 8px 12px !important;
            cursor: pointer;
            border-radius: 0.375rem;
        }

        /* Hover item */
        .ts-dropdown .ts-option:hover {
            background-color: #e5e7eb !important;
            /* gray-200 */
            color: #111827 !important;
        }

        /* Hover in dark mode */
        .dark .ts-dropdown .ts-option:hover {
            background-color: #374151 !important;
            /* gray-700 */
            color: white !important;
        }

        /* Selected item */
        .ts-dropdown .ts-option.selected {
            background-color: #3b82f6 !important;
            /* blue-500 */
            color: white !important;
        }

        /* ======================= */
        /* SELECTED VALUE (TAG)    */
        /* ======================= */

        .ts-wrapper.single .ts-control .item {
            color: #1f2937 !important;
        }

        .dark .ts-wrapper.single .ts-control .item {
            color: #e5e7eb !important;
        }

        /* DARK MODE */
        .dark .ts-control input {
            color: #e5e7eb !important;
            /* text-neutral-200 */
        }

        /* GLOBAL SELECT FIX - REMOVE DOUBLE ARROWS */
        select {
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            background-image: none !important;
        }

        /* For IE/Edge */
        select::-ms-expand {
            display: none !important;
        }

        .table-container {
            width: 100%;
            overflow: hidden;
        }

        .responsive-table {
            overflow-x: auto;
            max-width: 100%;
            display: block;
        }

        .main-table {
            min-width: 800px;
        }

        @media (max-width: 768px) {
            .responsive-table {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                overflow: visible !important;
            }

            .main-table {
                min-width: 100% !important;
            }

            .responsive-table table,
            .responsive-table thead,
            .responsive-table tbody,
            .responsive-table th,
            .responsive-table td,
            .responsive-table tr {
                display: block !important;
                width: 100% !important;
            }

            .responsive-table thead {
                display: none !important;
            }

            .responsive-table tr {
                background: white !important;
                margin-bottom: 1rem !important;
                border-radius: 1.5rem !important;
                padding: 1rem !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
                border: 1px solid rgba(0, 0, 0, 0.05) !important;
            }

            .dark .responsive-table tr {
                background: #262626 !important;
                border-color: #404040 !important;
            }

            .responsive-table td {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 0.75rem 0 !important;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
                text-align: right !important;
                min-height: 2.5rem !important;
                gap: 1rem !important;
            }

            .dark .responsive-table td {
                border-bottom-color: rgba(255, 255, 255, 0.05) !important;
            }

            .responsive-table td:last-child {
                border-bottom: none !important;
            }

            .responsive-table td::before {
                content: attr(data-label);
                font-weight: 800 !important;
                text-transform: uppercase !important;
                font-size: 0.65rem !important;
                letter-spacing: 0.05em !important;
                color: #71717a !important;
                text-align: left !important;
                flex-shrink: 0 !important;
            }
        }
    </style>


    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer data-navigate-once></script>
    <script>
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        OneSignalDeferred.push(async function (OneSignal) {
            await OneSignal.init({
                appId: "{{ env('ONESIGNAL_APP_ID') }}",
                notifyButton: {
                    enable: true, // Akan memunculkan ikon lonceng kecil di sudut kanan bawah web untuk minta izin
                },
            });

            // Daftarkan ID User yang sedang login ke OneSignal (Sangat Penting!)
            // Ini agar kita bisa mengirim notif spesifik hanya ke Admin, bukan ke Kasir
            @auth
                OneSignal.login("{{ auth()->user()->id }}");
            @endauth
        });
    </script>
    @livewireStyles
</head>
