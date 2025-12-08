<div>
    <video id="camera" autoplay class="w-100 rounded"></video>
    <canvas id="canvas" class="d-none"></canvas>

    <div class="d-flex justify-content-center gap-2 mt-3" id="photoButtons">
        <button class="btn btn-success" id="takePhoto">Ambil Foto</button>

    </div>

    <img id="preview" class="mt-3 w-100 rounded d-none">

    <!-- ACTION BUTTONS -->
    <div id="actionButtons">
        @if ($absensi && $absensi->status == 'complete')
        <div class="alert alert-success mt-4 mx-4 text-center">
            <h5><i class="fas fa-check-circle"></i> Anda sudah selesai bekerja hari ini.</h5>
            <p>Jam Masuk: {{ $absensi->jam_masuk }} | Jam Keluar: {{ $absensi->jam_keluar }}</p>
        </div>
        @elseif($absensi && $absensi->jam_masuk && !$absensi->jam_keluar)
        <div class="text-center">
            <button class="btn btn-dark mt-4" wire:click="submitClockIn">
                <i class="fas fa-sign-out-alt"></i> CLOCK OUT (PULANG)
            </button>
        </div>
        @else
        <div class="text-center">
            <button class="btn btn-primary mt-4" wire:click="submitClockIn">
                <i class="fas fa-sign-in-alt"></i> CLOCK IN (MASUK)
            </button>
        </div>
        @endif
    </div>
    <div class="d-flex justify-content-center gap-2 mt-3" id="photoButtons">
        <button class="btn btn-secondary d-none" id="retakePhoto">Foto Ulang</button>
    </div>
    <input type="hidden" id="foto">
    <input type="hidden" id="lokasi">



    <script>
        const video = document.getElementById('camera');
        const canvas = document.getElementById('canvas');
        const takePhotoBtn = document.getElementById('takePhoto');
        const retakePhotoBtn = document.getElementById('retakePhoto');
        const preview = document.getElementById('preview');
        const actionButtons = document.getElementById('actionButtons');

        let stream;

        // START CAMERA
        async function startCamera() {
            stream = await navigator.mediaDevices.getUserMedia({
                video: true
            });
            video.srcObject = stream;
        }

        // STOP CAMERA
        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        }

        startCamera();

        // TAKE PHOTO
        takePhotoBtn.addEventListener('click', () => {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0);

            const imageData = canvas.toDataURL('image/jpeg');

            document.getElementById('foto').value = imageData;

            preview.src = imageData;
            preview.classList.remove('d-none');

            // HIDE CAMERA & BUTTONS
            video.classList.add('d-none');
            takePhotoBtn.classList.add('d-none');
            actionButtons.classList.add('d-none');

            // SHOW RETAKE
            retakePhotoBtn.classList.remove('d-none');

            stopCamera();
        });

        // RETAKE PHOTO
        retakePhotoBtn.addEventListener('click', () => {
            preview.classList.add('d-none');
            video.classList.remove('d-none');
            takePhotoBtn.classList.remove('d-none');
            actionButtons.classList.remove('d-none');
            retakePhotoBtn.classList.add('d-none');

            document.getElementById('foto').value = '';

            startCamera();
        });
    </script>

</div>
