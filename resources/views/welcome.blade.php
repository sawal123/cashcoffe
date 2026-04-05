<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temuan Space</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Essential Resets since preflight is false */
        *,
        ::before,
        ::after {
            box-sizing: border-box;
            border-width: 0;
            border-style: solid;
            border-color: #e5e7eb;
        }

        html {
            line-height: 1.5;
            -webkit-text-size-adjust: 100%;
            font-family: 'Outfit', sans-serif;
        }

        body {
            margin: 0;
            line-height: inherit;
        }

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        a {
            color: inherit;
            text-decoration: inherit;
        }

        button {
            cursor: pointer;
        }

        svg {
            display: block;
            vertical-align: middle;
        }

        /* Custom Styles */
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #eff6ff;
            background-image:
                radial-gradient(ellipse 80% 40% at 50% 0%, rgba(147, 197, 253, 0.45) 0%, transparent 70%),
                radial-gradient(#bfdbfe 1px, transparent 1px);
            background-size: 100% 100%, 24px 24px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 10px 40px -10px rgba(30, 58, 138, 0.1);
        }

        /* Animations */
        @keyframes fade-in-up {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fade-in-up 0.8s ease-out forwards;
        }

        .delay-100 {
            animation-delay: 100ms;
        }

        .delay-200 {
            animation-delay: 200ms;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0px);
            }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }

            33% {
                transform: translate(30px, -50px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }

            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }

        .animate-blob {
            animation: blob 7s infinite;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        /* Glassmorphism Navbar */
        .navbar-glass {
            background: rgba(219, 234, 254, 0.30);
            backdrop-filter: blur(24px) saturate(200%) brightness(108%);
            -webkit-backdrop-filter: blur(24px) saturate(200%) brightness(108%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.80);
            box-shadow:
                0 8px 32px -8px rgba(37, 99, 235, 0.15),
                0 1px 0 rgba(255, 255, 255, 0.95) inset;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col text-slate-800 antialiased selection:bg-blue-200 selection:text-blue-900">

    <!-- Navbar -->
    <nav class="navbar-glass fixed w-full z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex-shrink-0 flex items-center gap-2">
                    <!-- Brand -->
                    <a href="/"
                        class="text-2xl font-extrabold text-blue-950 tracking-tight transition hover:opacity-80">
                        Temuan<span class="text-blue-600">Space</span>
                    </a>
                </div>
                <!-- Desktop Menu -->
                <!-- <div class="flex items-center space-x-6">
                    @auth
                        <a href="{{ url('/dashboard') }}"
                            class="text-sm font-bold text-blue-900 hover:text-blue-600 transition border border-transparent hover:border-blue-200 px-4 py-2 rounded-full hover:bg-blue-50/50">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}"
                            class="text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 transition px-6 py-2.5 rounded-full shadow-md shadow-blue-200 hover:shadow-lg hover:shadow-blue-300 transform hover:-translate-y-0.5">Log
                            in</a>
                    @endauth
                </div> -->
            </div>
        </div>
    </nav>

    <!-- Main Hero Section -->
    <main class="flex-grow flex flex-col justify-center relative overflow-hidden pt-24">

        <!-- Decorative blobs -->
        <div
            class="absolute top-20 right-0 -mr-20 w-96 h-96 rounded-full bg-blue-200/50 mix-blend-multiply filter blur-3xl opacity-70 animate-blob">
        </div>
        <div
            class="absolute top-40 left-0 -ml-20 w-72 h-72 rounded-full bg-blue-300/40 mix-blend-multiply filter blur-3xl opacity-60 animate-blob animation-delay-2000">
        </div>

        <div
            class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative w-full py-16 lg:py-24 flex flex-col lg:flex-row items-center gap-20 lg:gap-12">

            <!-- Left Content: Text & CTA -->
            <div class="lg:w-1/2 text-center lg:text-left z-10 flex flex-col items-center lg:items-start group">

                <!-- Status Badge -->
                <div
                    class="animate-fade-in-up inline-flex items-center mt-10 gap-2.5 px-4 py-1.5 rounded-full bg-white/80 backdrop-blur-sm text-blue-700 ring-1 ring-inset ring-blue-200/50 mb-8 shadow-sm">
                    <span class="relative flex h-2.5 w-2.5">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                    </span>
                    <span class="text-sm font-semibold tracking-wide">We Are Open</span>
                </div>

                <!-- Heading -->
                <h1
                    class="animate-fade-in-up delay-100 text-5xl sm:text-6xl lg:text-7xl font-extrabold text-slate-900 leading-[1.1] mb-6 tracking-tight">
                    Welcome to <br />
                    <span
                        class="text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-blue-400 drop-shadow-sm">Temuan
                        Space</span>
                </h1>

                <p
                    class="animate-fade-in-up delay-200 mt-2 text-lg sm:text-xl text-slate-600 mb-10 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    Ruang kreasi dan kopi pilihan. Temukan inspirasi dan ciptakan momen terbaikmu dalam suasana yang
                    tenang dan estetik.
                </p>

                <!-- Buttons -->
                <div
                    class="animate-fade-in-up delay-200 flex flex-col sm:flex-row justify-center lg:justify-start gap-4 sm:gap-6 w-full sm:w-auto">
                    <a href="https://maps.app.goo.gl/ayw9Jy6QgQQ58AgU9" target="_blank" rel="noopener noreferrer"
                        class="relative flex items-center justify-center px-8 py-4 text-base font-bold text-white transition-all duration-300 bg-blue-600 rounded-2xl hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100 shadow-xl shadow-blue-600/20 overflow-hidden group/btn hover:-translate-y-1">
                        <span
                            class="absolute inset-0 w-full h-full -mt-1 rounded-lg opacity-30 bg-gradient-to-b from-transparent via-transparent to-black"></span>
                        <span class="relative flex items-center gap-2.5">
                            <svg class="w-5 h-5 transition-transform group-hover/btn:scale-110" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Kunjungi Kami
                        </span>
                    </a>

                    <a href="https://www.instagram.com/temuanspace/" target="_blank" rel="noopener noreferrer"
                        class="flex items-center justify-center px-8 py-4 text-base font-bold text-slate-700 transition-all duration-300 bg-white border-2 border-slate-100 rounded-2xl hover:border-blue-200 hover:text-blue-600 hover:bg-blue-50/50 shadow-sm hover:shadow-md focus:outline-none focus:ring-4 focus:ring-slate-100 hover:-translate-y-1 group/ig">
                        <svg class="w-5 h-5 mr-2.5 transition-transform group-hover/ig:rotate-6" fill="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"
                                clip-rule="evenodd" />
                        </svg>
                        Instagram
                    </a>
                </div>
            </div>

            <!-- Right Content: Info Card -->
            <div class="lg:w-1/2 mt-10 w-full  max-w-lg z-10 animate-fade-in-up delay-200 animate-float">
                <div
                    class="glass-card p-10 rounded-2xl w-full rounded-[2rem] p-8 sm:p-10 relative overflow-hidden transition-transform duration-500 hover:shadow-2xl hover:-translate-y-2">
                    <!-- Top accent line -->
                    <div
                        class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-blue-400 via-blue-500 to-blue-600">
                    </div>

                    <h2
                        class="text-2xl sm:text-3xl font-extrabold text-blue-950 mb-8 pb-4 border-b border-blue-100/60 inline-flex items-center gap-3">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Informasi
                    </h2>

                    <div class="space-y-8">
                        <!-- Open Hours -->
                        <div class="flex items-start gap-5 group/item">
                            <div
                                class="flex-shrink-0 w-14 h-14 rounded-2xl bg-white/70 shadow-sm flex items-center justify-center text-blue-600 group-hover/item:bg-blue-600 group-hover/item:text-white transition-all duration-300 transform group-hover/item:scale-110">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="pt-1">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em] mb-1.5">Jam
                                    Operasional</h3>
                                <p class="text-xl font-bold text-slate-800">11:00 s/d 22:00</p>
                                <span
                                    class="inline-flex mt-2 items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Buka Setiap Hari
                                </span>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="w-full h-px bg-gradient-to-r from-transparent via-blue-200/50 to-transparent"></div>

                        <!-- Location -->
                        <div class="flex items-start gap-5 group/item">
                            <div
                                class="flex-shrink-0 w-14 h-14 rounded-2xl bg-white/70 shadow-sm flex items-center justify-center text-blue-600 group-hover/item:bg-blue-600 group-hover/item:text-white transition-all duration-300 transform group-hover/item:scale-110">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <div class="pt-1">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em] mb-1.5">Lokasi
                                </h3>
                                <p class="text-[15px] text-slate-700 leading-relaxed font-medium pr-4">
                                    Jl. Tenis No.30, Ps. Merah Bar., Kec. Medan Kota, Kota Medan, Sumatera Utara 20216
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-blue-100/60 bg-white/40 backdrop-blur-sm py-8 relative z-10 mt-auto">
        <div
            class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-4">
            <p class="text-sm text-slate-500 font-medium">
                &copy; {{ date('Y') }} Temuan Space. All rights reserved.
            </p>
            <div class="font-extrabold text-slate-400 text-lg opacity-80">
                Temuan<span class="text-blue-500">Space</span>
            </div>
        </div>
    </footer>

</body>

</html>