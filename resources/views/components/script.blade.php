    <!-- jQuery library js -->
    <script src="{{ asset('assets/js/lib/jquery-3.7.1.min.js') }}" data-navigate-once></script>
    <!-- Apex Chart js -->
    <script src="{{ asset('assets/js/lib/apexcharts.min.js') }}" data-navigate-once></script>


    <script src="{{ asset('assets/js/lib/iconify-icon.min.js') }}" data-navigate-once></script>
    <!-- jQuery UI js -->
    <script src="{{ asset('assets/js/lib/jquery-ui.min.js') }}" data-navigate-once></script>
    <!-- Vector Map js -->
    <script src="{{ asset('assets/js/lib/jquery-jvectormap-2.0.5.min.js') }}" data-navigate-once></script>
    <script src="{{ asset('assets/js/lib/jquery-jvectormap-world-mill-en.js') }}" data-navigate-once></script>
    <!-- Popup js -->
    <script src="{{ asset('assets/js/lib/magnifc-popup.min.js') }}" data-navigate-once></script>
    <!-- Slick Slider js -->
    <script src="{{ asset('assets/js/lib/slick.min.js') }}" data-navigate-once></script>
    <!-- prism js -->
    <script src="{{ asset('assets/js/lib/prism.js') }}" data-navigate-once></script>
    <!-- file upload js -->
    <script src="{{ asset('assets/js/lib/file-upload.js') }}" data-navigate-once></script>
    <!-- audio player -->
    <script src="{{ asset('assets/js/lib/audioplayer.js') }}" data-navigate-once></script>

    <script src="{{ asset('assets/js/flowbite.min.js') }}" data-navigate-once></script>
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script> --}}
    <script>
        document.addEventListener("livewire:navigated", function() {
            if (typeof initFlowbite === "function") {
                initFlowbite(); // inisialisasi ulang komponen Flowbite
            }
        });
    </script>
    <!-- main js -->
    <script src="{{ asset('assets/js/app.js') }}" data-navigate-once></script>



    <?php echo isset($script) ? $script : ''; ?>
