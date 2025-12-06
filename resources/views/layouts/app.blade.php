@props(['data' => [], 'categories' => []])
<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en" data-theme="light">

<x-head />
<body class="dark:bg-neutral-800 bg-neutral-100 dark:text-white">

    <!-- ..::  header area start ::.. -->
    <x-sidebar />
    <!-- ..::  header area end ::.. -->

    <main class="dashboard-main">

        <!-- ..::  navbar start ::.. -->
        <x-navbar />
        <!-- ..::  navbar end ::.. -->
        <div class="dashboard-main-body">

            <!-- ..::  breadcrumb  start ::.. -->
            <x-breadcrumb title='{{ isset($title) ? $title : '' }}' subTitle='{{ isset($subTitle) ? $subTitle : '' }}' />
            <!-- ..::  header area end ::.. -->

            {{-- @yield('content') --}}
            <div x-data="{ modalActive: false }">
                <div :class="{ 'blur-sm scale-[0.99]': modalActive }" class="transition duration-300 ease-in-out">
                    {{-- Semua konten halaman --}}
                    {{ $slot }}
                </div>
            </div>


        </div>
        <!-- ..::  footer  start ::.. -->
        <x-footer />
        <!-- ..::  footer area end ::.. -->

    </main>

    <x-script :script="$script ?? ''" />

    <script>
        window.chartData = {
            data: @json($data ?? []),
            categories: @json($categories ?? []),
        };
    </script>

    <script>
        function initTomSelect(id, eventName) {
            const el = document.getElementById(id);
            if (!el) return; // kalau elemen belum ada, jangan init

            // Hindari double init
            if (el.tomSelect) return;

            const select = new TomSelect(el, {
                placeholder: 'Cari...',
                allowEmptyOption: true,
                allowClear: true,
            });

            select.on('change', function(value) {
                Livewire.dispatch(eventName, {
                    value
                });
            });
        }

        document.addEventListener('livewire:navigated', () => {
            initTomSelect('menuSelect', 'setMenu');
            initTomSelect('ingredientSelect', 'setIngredient');
        });

        document.addEventListener('livewire:update', () => {
            // Saat DOM berubah (misal menu dipilih, ingredient muncul)
            initTomSelect('ingredientSelect', 'setIngredient');
        });
    </script>


    <script src="{{ asset('assets/js/chartDashboard.js') }}" data-navigate-once></script>

    @livewireScripts
</body>

</html>
