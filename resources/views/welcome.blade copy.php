<!DOCTYPE html>
<html lang="en">

<head>
    <!-- ========== Meta Tags ========== -->
    <meta charset="UTF-8">
    <meta name="description" content="Temuan Space">
    <meta name="keywords" content="koffie, coffee, temuan">
    <meta name="author" content="Temuan Space">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <!-- ========== Title ========== -->
    <title>Temuan Space</title>
    <link rel="icon" type="image/png') }}" href="{{ asset('logo/logow.png') }}" sizes="16x16">

    <!-- ========== STYLESHEETS ========== -->
    <!-- Bootstrap CSS -->
    <link href="{{ asset('frontend/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Fonts Awesome CSS -->
    <link href="{{ asset('frontend/css/font-awesome.min.css') }}" rel="stylesheet">
    <!-- Et line icon CSS -->
    <link href="{{ asset('frontend/css/et-line-icon.css') }}" rel="stylesheet">
    <!-- Owl Carousel CSS -->
    <link href="{{ asset('frontend/css/owl.carousel.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/css/owl.transitions.css') }}" rel="stylesheet">
    <link href="{{ asset('frontend/css/owl.theme.css') }}" rel="stylesheet">
    <!-- magnific CSS -->
    <link href="{{ asset('frontend/css/magnific-popup.css') }}" rel="stylesheet" />
    <!-- Animate CSS -->
    <link href="{{ asset('frontend/css/animate.min.css') }}" rel="stylesheet" />
    <!-- Custom styles for this template -->
    <link href="{{ asset('frontend/css/style.css') }}" rel="stylesheet">
    <!--responsive css -->
    <link href="{{ asset('frontend/css/responsive.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('frontend/css/colors/yellow.css') }}">
    <!-- teamplate colors -->
    <!-- <link rel="stylesheet" href="css/colors/turquoise.css">-->
    <!-- <link rel="stylesheet" href="css/colors/light-green.css"> -->
    <!-- <link rel="stylesheet" href="css/colors/purple.css"> -->
    <!-- <link rel="stylesheet" href="css/colors/light-blue.css"> -->
    <!-- <link rel="stylesheet" href="css/colors/brown.css"> -->
</head>

<body>

    <!-- ========== preloader Start ========== -->
    <div class="preloader-wrap">
        <div class="preloader">
            <div class="preloader-top">
                <div class="preloader-top-cup">
                    <i class="fa fa-coffee" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- ========== preloader End ========== -->

    <!-- ========== Header ========== -->
    <header class="navbar header navbar-fixed-top">
        <div class="container">
            <!-- Navbar-header -->
            <div class=" navbar-header">
                <button type="button" class="navbar-toggle mobile_menu_btn" data-toggle="collapse"
                    data-target=".navbar-collapse" aria-expanded="false">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <!-- LOGO -->
                <a class="navbar-brand logo" href="/">
                    Temuan Space
                </a>
            </div>
            <!-- end navbar-header -->

            <!-- menu -->
            <div class="navbar-collapse collapse" id="data-scroll">
                <ul class="nav navbar-nav navbar-right">
                    <li class="active"><a href="#home">Home</a></li>
                    @auth
                        <li>
                            <a href="{{ url('/dashboard') }}">
                                Dashboard
                            </a>
                        </li>
                    @else
                        <li>
                            <a href="{{ route('login') }}"> Log in
                            </a>
                        </li>

                        {{-- @if (Route::has('register'))
                            <li>
                                <a href="{{ route('register') }}">
                                    Register
                                </a>
                            </li>
                        @endif --}}
                    @endauth
                </ul>
            </div>
            <!--/Menu -->
        </div>
        <!-- end container -->
    </header>
    <!-- ========== Header End ========== -->

    <!-- ========== hero section ========== -->
    <section id="home" data-stellar-background-ratio="0.5" class="hero hero_full_screen hero_parallax text-center">
        <div class="bg-overlay opacity-6"></div>
        <div class="hero_parallax_inner">
            <div class="container">
                <a class="hero-logo fadeIn animated wow" data-wow-delay=".1s" href="#"><img src="img/logo.png"
                        alt="" /></a>
                <h1 class="intro fadeIn animated wow" data-wow-delay=".2s">We Are Open!</h1>
                <a href="https://linktr.ee/temuanspace" class="buttons scroll zoomIn animated wow" data-wow-delay=".3s">Location</a>
            </div>
        </div>
    </section>
    <!-- ========== hero section  End ========== -->

    <!-- ========== contact section End ========== -->
    <!-- ========== footer ========== -->
    {{-- <footer id="footer" class="pt50 pb50 footer-area bg-black">
        <div class="container">
            <div class="row">
                <div class="col-sm-3 text-center-xs">
                    <h3 class="logo animated fadeInLeft wow" data-wow-delay=".1s">Koffie</h3>
                </div>
                <div class="col-sm-9 text-right text-center-xs">
                    <p class="animated fadeInRight wow" data-wow-delay=".2s">&copy;
                        <script>
                            document.write(new Date().getFullYear());
                        </script> koffie - All Rights Reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer> --}}
    <!-- ========== footer section End ========== -->
    <!-- JQUERY -->
    <script type="text/javascript" src="{{ asset('frontend/js/jquery.min.js') }}"></script>
    <!-- Bootstrap js -->
    <script type="text/javascript" src="{{ asset('frontend/js/bootstrap.min.js') }}"></script>
    <!-- Onepage nav js -->
    <script type="text/javascript" src="{{ asset('frontend/js/jquery.nav.js') }}"></script>
    <!-- Stellar js -->
    <script type="text/javascript" src="{{ asset('frontend/js/jquery.stellar.min.js') }}"></script>
    <!-- Owl curosel js -->
    <script type="text/javascript" src="{{ asset('frontend/js/owl.carousel.min.js') }}"></script>
    <!-- magnific popuop js -->
    <script type="text/javascript" src="{{ asset('frontend/js/jquery.magnific-popup.min.js') }}"></script>
    <!-- WOW JS -->
    <script src="{{ asset('frontend/js/wow.min.js') }}"></script>
    <!-- Custom js -->
    <script type="text/javascript" src="{{ asset('frontend/js/app.js') }}"></script>
    </div>
</body>

</html>
