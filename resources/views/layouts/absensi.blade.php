<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Travgo</title>

    <!-- favicon -->
    <link rel="shortcut icon" href="{{ asset('absensi/images/favicon.png') }}" type="image/x-icon">

    <!-- bootstrap -->
    <link rel="stylesheet" href="{{ asset('absensi/css/bootstrap.min.css') }}">

    <!-- swiper -->
    <link rel="stylesheet" href="{{ asset('absensi/css/swiper-bundle.min.css') }}">

    <!-- datepicker -->
    <link rel="stylesheet" href="{{ asset('absensi/css/jquery.datetimepicker.css') }}">

    <!-- jquery ui -->
    <link rel="stylesheet" href="{{ asset('absensi/css/jquery-ui.min.css') }}">

    <!-- common -->
    <link rel="stylesheet" href="{{ asset('absensi/css/common.css') }}">

    <!-- animations -->
    <link rel="stylesheet" href="{{ asset('absensi/css/animations.css') }}">

    <!-- welcome -->
    <link rel="stylesheet" href="{{ asset('absensi/css/welcome.css') }}">

    <!-- auth -->
    <link rel="stylesheet" href="{{ asset('absensi/css/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('absensi/css/home.css') }}">
    @vite([])
</head>

<body class="scrollbar-hidden">
    <!-- splash-screen start -->
    {{-- <section id="preloader" class="spalsh-screen">
        <div class="circle text-center">
            <div>
                <h1>Travgo</h1>
                <p>Discover Your Destinition</p>
            </div>
        </div>
        <div class="loader-spinner">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </section> --}}
    <!-- splash-screen end -->

    <main class="home">
        <section class="wrapper dz-mode">
            @include('layouts.partial.nav')
        </section>
        {{ $slot }}
        @include('layouts.partial.footer')
    </main>


    <!-- jquery -->
    <script src="{{ asset('absensi/js/jquery-3.6.1.min.js') }}"></script>

    <!-- bootstrap -->
    <script src="{{ asset('absensi/js/bootstrap.bundle.min.js') }}"></script>

    <!-- jquery ui -->
    <script src="{{ asset('absensi/js/jquery-ui.js') }}"></script>

    <!-- mixitup -->
    <script src="{{ asset('absensi/js/mixitup.min.js') }}"></script>

    <!-- gasp -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/gsap.min.js"></script>

    <!-- draggable -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.3/Draggable.min.js"></script>

    <!-- swiper -->
    <script src="{{ asset('absensi/js/swiper-bundle.min.js') }}"></script>

    <!-- datepicker -->
    <script src="{{ asset('absensi/js/jquery.datetimepicker.full.js') }}"></script>

    <!-- google-map api -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCodvr4TmsTJdYPjs_5PWLPTNLA9uA4iq8&callback=initMap"
        type="text/javascript"></script>

    <!-- script -->
    <script src="{{ asset('absensi/js/script.js') }}"></script>
</body>

</html>
