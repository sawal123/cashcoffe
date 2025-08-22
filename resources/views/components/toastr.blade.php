<div>
    @script
        <script>
            console.log("Toast Script Loaded");

            // Cegah listener ganda
            if (!window.toastrListenerAdded) {
                window.addEventListener('showToast', event => {
                    const {
                        message,
                        title,
                        type
                    } = event.detail;

                    toastr[type](message, title, {
                        positionClass: 'toast-top-right',
                        closeButton: true,
                        progressBar: true,
                        showMethod: "fadeIn",
                        hideMethod: "fadeOut"
                    });

                    console.log("Toast triggered:", message);
                });

                window.toastrListenerAdded = true;
            }
        </script>
    @endscript

</div>
