<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temuan Space</title>
    <link rel="icon" type="image/png') }}" href="{{ asset('logo/logow.png') }}" sizes="16x16">
    <!-- google fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
        rel="stylesheet">
    <!-- remix icon font css  -->
    <link rel="stylesheet" href="{{ asset('assets/css/remixicon.css') }}">
    <!-- Apex Chart css -->
    <link rel="stylesheet" href="{{ asset('assets/css/lib/apexcharts.css') }}">
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
    {{-- @vite(['resources/css/app.css'])
    @vite(['resources/js/app.js']) --}}
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    
    <link rel="stylesheet" href="{{ asset('build/assets/ui-BvFyUMto.css') }}">
    <link rel="stylesheet" href="{{ asset('build/assets/app-BUd4vcqu.css ') }}">
    <link rel="stylesheet" href="{{ asset('build/assets/app-BTaMLvJ2.css') }}">
    <link rel="stylesheet" href="{{ asset('build/assets/app-OScb3ZFM.js') }}">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <!-- TomSelect CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">

    <!-- TomSelect JS -->
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />

    <script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>



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
    </style>

    @livewireStyles
</head>
