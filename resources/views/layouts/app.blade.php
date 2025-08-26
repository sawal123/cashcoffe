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

    <!-- ..::  scripts  start ::.. -->
    {{-- <x-script script='{!! isset($script) ? $script : '' !!}' /> --}}
    <x-script :script="$script ?? ''" />


    <!-- ..::  scripts  end ::.. -->

    @livewireScripts
</body>

</html>
