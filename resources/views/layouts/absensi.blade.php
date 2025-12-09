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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">


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
            <div></div>p
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

    <main class="">
        @if (!request()->routeIs('absensi.clock.in'))
        <div class="header p-3 d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center justify-content-between gap-14">
                <div class="image shrink-0 rounded-full overflow-hidden">
                    <img src="{{ asset('absensi/images/home/avatar.png') }}" alt="avatar"
                        class="w-100 h-100 object-fit-cover">
                </div>
                <div>
                    <h5>Hi, {{ Auth::user()->name }}</h5>
                    <p class="d-flex align-items-center gap-04">
                        <img src="{{ asset('absensi/svg/map-marker.svg') }}" alt="icon">
                        <span id="userLocation">Mendeteksi lokasi...</span>
                    </p>
                </div>
            </div>
            <ul class="d-flex align-items-center gap-3">
                <li>
                    <a href="notification.html"
                        class="d-flex align-items-center justify-content-center rounded-full border p-2 position-relative">
                        <img src="{{ asset('absensi/svg/bell-black.svg') }}" width="24" height="24" alt="icon">
                        <span class="dot"></span>
                    </a>
                </li>
                <li>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                        class="d-flex align-items-center justify-content-center rounded-full  border p-2 position-relative">
                        <img src="{{ asset('absensi/svg/exit.svg') }}" width="24" height="24" alt="icon">
                        <span class="dot"></span>
                    </a>
                </li>
            </ul>
        </div>
        @endif
        {{-- <section class="wrapper dz-mode">
            @include('layouts.partial.nav')
        </section> --}}
        {{ $slot }}
        {{-- @include('layouts.partial.footer') --}}
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const el = document.getElementById('userLocation');

            if (!navigator.geolocation) {
                el.innerText = "Browser tidak mendukung lokasi";
                return;
            }

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                        try {
                            const {
                                latitude,
                                longitude
                            } = position.coords;

                            const res = await fetch(
                                `/reverse-geocode?lat=${latitude}&lon=${longitude}`
                            );

                            if (!res.ok) throw new Error('Backend error');

                            const data = await res.json();

                            el.innerText =
                                data.address.city ||
                                data.address.suburb ||
                                data.address.neighbourhood ||
                                data.address.country ||
                                'Lokasi tidak ditemukan';

                        } catch (e) {
                            el.innerText = "Gagal membaca lokasi";
                        }
                    },
                    () => el.innerText = "Izin lokasi ditolak", {
                        enableHighAccuracy: true,
                        timeout: 10000
                    }
            );
        });
    </script>

</body>

</html>
