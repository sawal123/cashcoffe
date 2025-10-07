<div>
    @script
        <script>
            if (!window.toastListenerInitialized) {
                window.toastListenerInitialized = true;

                window.addEventListener('showToast', event => {
                    const {
                        message,
                        type,
                        title
                    } = event.detail;

                    Toastify({
                        text: `${message}`,
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        close: true,
                        style: {
                            background: type === 'success' ?
                                "linear-gradient(to right, #00b09b, #96c93d)" : type === 'error' ?
                                "linear-gradient(to right, #e74c3c, #c0392b)" :
                                "linear-gradient(to right, #3498db, #2ecc71)"
                        }
                    }).showToast();
                });
            }
        </script>
    @endscript
</div>
