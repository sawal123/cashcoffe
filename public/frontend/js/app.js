/*
 * Koffie One Page HTML Landing Page
 * Build Date: december 2016
 * Author: ThemeAtelier
 * Copyright (C) 2016 ThemeAtelier
 */
/**
 * Table of contents
 * -----------------------------------
 * 1.0 JQUERY WINDOW LOAD FUNCTION
	* 1.1 PRELOADER
 * 2.0 JQUERY DOCUMENT READY FUNCTION
	* 2.1 ONE PAGE NAVIGATION
	* 2.2 TOP FIXED NAVBAR
	* 2.3 ANCOR SOMOOTH SCROLL
	* 2.4 STELLAR JS
	* 2.5 HERO SLIDER
	* 2.6 TESTIMONIAL CAROUSEL
	* 2.7 MAGNIFIC POPUP
	* 2.8 MAGNIFIC POPUP GALLERY
	* 2.9 LOAD MORE GALLERY ITEM
	* 2.10 WOW JS
 ==================
 *
 */
(function($) {
    "use strict"; // this function is executed in strict mode
/* =================================
   1.0 JQUERY WINDOW LOAD FUNCTION
=================================== */
$(window).load( function() {	
    /******************** 1.1 PRELOADER ********************/
    // will first fade out the loading animation
    $(".preloader").fadeOut();
    // will fade out the whole DIV that covers the website.
    $(".preloader-wrap").delay(1000).fadeOut("slow");
});

/* =================================
   2.0 JQUERY DOCUMENT READY FUNCTION
=================================== */
    $(document).ready(function() {

        /******************** 2.1 ONE PAGE NAVIGATION ********************/
        $('.navbar-nav').onePageNav({
            currentClass: 'active',
            scrollOffset: 74
        });

		/******************** 4. NAV COLLAPSE ********************/
	
		$('.navbar-nav li a').on('click', function () {
			
			var toggle = $('.navbar-toggle').is(':visible');
			if (toggle) {
				$('.navbar-collapse').collapse('hide');
			}
		});
	
				
        /******************** 2.2 TOP FIXED NAVBAR ********************/
        $(document).on('scroll', function() {
            var activeClass = 'navbar-home',
                ActiveID = '.header',
                scrollPos = $(this).scrollTop();

            if (scrollPos > 10) {
                $(ActiveID).addClass(activeClass);
            } else {
                $(ActiveID).removeClass(activeClass);
            }
        });
        /******************** 2.3 ANCOR SOMOOTH SCROLL ********************/
       
            $('a.scroll[href*="#"]:not([href="#"])').on('click', function() {
                if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
                    var target = $(this.hash);
                    target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
                    if (target.length) {
                        $('html, body').animate({
                            scrollTop: target.offset().top
                        }, 1000);
                        return false;
                    }
                }
            });
        
        /******************** 2.4 STELLAR JS ********************/
        $(window).stellar({
            horizontalScrolling: false,
        });
        /******************** 2.5 HERO SLIDER ********************/
		$(".fullscreen-slider").owlCarousel({
			slideSpeed: 350,
			singleItem: true,
			navigation: false,
			pagination: true,
			responsive: true,
			autoPlay:true,
			transitionStyle: "fade",
		});
		/******************** 2.6 TESTIMONIAL CAROUSEL ********************/
        $(".testimonial-carousel").owlCarousel({
            itemsCustom: [
                [0, 1],
                [600, 1],
                [720, 1],
                [1000, 2]
            ],
            slideSpeed: 1000,
            pagination: true,
            autoPlay: false,
        });
        /******************** 2.7 MAGNIFIC POPUP ********************/
        $('.gallery-popup').magnificPopup({
            type: 'image',
            removalDelay: 500,
        });
        /******************** 2.8 MAGNIFIC POPUP GALLERY ********************/
        $('.zoom-gallery').magnificPopup({
            delegate: 'a',
            type: 'image',
            closeOnContentClick: false,
            closeBtnInside: false,
            mainClass: 'mfp-with-zoom mfp-img-mobile',
            gallery: {
                enabled: true
            },
            zoom: {
                enabled: true,
                duration: 300, // don't foget to change the duration also in CSS
                opener: function(element) {
                    return element.find('img');
                }
            }
        });
        /******************** 2.9 LOAD MORE GALLERY ITEM ********************/
            $(".grid-item").slice(0, 6).show();
            $("#loadmore").on('click', function(e) {
                e.preventDefault();
                $(".grid-item:hidden").slice(0, 3).slideDown();
                if ($(".grid-item:hidden").length == 0) {
                    $("#load").fadeOut('slow');
                };
            });
		/******************** 2.10 WOW JS ********************/
		var wow = new WOW(
		 {
			animateClass: 'animated',
			offset: 50,
			mobile: true
		 }
		);
		wow.init();
    });
	
})(jQuery);