<div class="container py-3" style="max-height: 100vh; overflow-y: auto">
    {{-- BACK BUTTON --}}
    <div class="mb-3">
        <button onclick="history.back()" class="btn btn-light d-flex align-items-center gap-2 shadow-sm">
            <i class="fas fa-arrow-left"></i>
            <span>Kembali</span>
        </button>
    </div>
    {{-- CAMERA CARD --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body text-center">

            {{-- CAMERA --}}
            <video id="camera" autoplay class="w-100 rounded"></video>
            <canvas id="canvas" class="d-none"></canvas>

            {{-- PREVIEW FOTO --}}
            <img id="preview" class="mt-3 w-100 rounded d-none">

            {{-- INFO SETELAH FOTO --}}
            <div id="photoInfo" class="d-none mt-3">
                <div class="alert alert-light small text-start mb-2">
                    <div><strong>Waktu:</strong> <span id="captureTime">-</span></div>
                    <div><strong>Lokasi:</strong></div>
                    <iframe id="map" class="w-100 rounded" height="180" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>

        </div>
    </div>

    {{-- BUTTON FOTO --}}
    <div class="d-flex justify-content-center gap-2 mb-3">
        <button class="btn btn-success" id="takePhoto">
            <i class="fas fa-camera"></i> Ambil Foto
        </button>

        <button class="btn btn-secondary d-none" id="retakePhoto">
            <i class="fas fa-redo"></i> Foto Ulang
        </button>
    </div>

    {{-- ACTION BUTTON --}}
    <div id="actionButtons" class="text-center mb-4 d-none">

        {{-- ✅ SUDAH CLOCK OUT --}}
        @if ($absensi && $absensi->jam_masuk && $absensi->jam_keluar)
        <div class="alert alert-success">
            <strong>Selesai bekerja hari ini</strong><br>
            Masuk: {{ $absensi->jam_masuk }} |
            Pulang: {{ $absensi->jam_keluar }}
        </div>

        {{-- ✅ CLOCK OUT --}}
        @elseif ($absensi && $absensi->jam_masuk)
        <button id="submitBtn" class="btn btn-dark btn-lg w-100" wire:click="submitClockIn" disabled>
            <i class="fas fa-sign-out-alt"></i> CLOCK OUT
        </button>

        {{-- ✅ CLOCK IN --}}
        @else
        <button id="submitBtn" class="btn btn-primary btn-lg w-100" wire:click="submitClockIn" disabled>
            <i class="fas fa-sign-in-alt"></i> CLOCK IN
        </button>
        @endif

    </div>

    <input type="hidden" id="foto">
    <input type="hidden" id="lokasi">

</div>

<script>
    const video = document.getElementById('camera');
    const canvas = document.getElementById('canvas');
    const takePhotoBtn = document.getElementById('takePhoto');
    const retakePhotoBtn = document.getElementById('retakePhoto');
    const preview = document.getElementById('preview');
    const submitBtn = document.getElementById('submitBtn');

    const actionButtons = document.getElementById('actionButtons');
    const photoInfo = document.getElementById('photoInfo');
    const captureTimeEl = document.getElementById('captureTime');
    const mapIframe = document.getElementById('map');

    let stream;

    async function startCamera() {
        stream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = stream;
    }

    function stopCamera() {
        stream?.getTracks().forEach(track => track.stop());
    }

    startCamera();

    takePhotoBtn.addEventListener('click', () => {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        canvas.getContext('2d').drawImage(video, 0, 0);
        const imageData = canvas.toDataURL('image/jpeg');

        document.getElementById('foto').value = imageData;
        preview.src = imageData;

        preview.classList.remove('d-none');
        video.classList.add('d-none');

        takePhotoBtn.classList.add('d-none');
        retakePhotoBtn.classList.remove('d-none');

        // ✅ tampilkan tombol submit
        actionButtons.classList.remove('d-none');
        submitBtn.disabled = false;

        // ✅ tampilkan waktu ambil foto
        const now = new Date();
        captureTimeEl.textContent = now.toLocaleString('id-ID');

        // ✅ ambil lokasi (TANPA ubah logic submit)
        navigator.geolocation.getCurrentPosition((pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            document.getElementById('lokasi').value = lat + ',' + lng;

            mapIframe.src =
                `https://www.google.com/maps?q=${lat},${lng}&hl=id&z=16&output=embed`;

            photoInfo.classList.remove('d-none');
        });

        stopCamera();
    });

    retakePhotoBtn.addEventListener('click', () => {
        preview.classList.add('d-none');
        video.classList.remove('d-none');

        takePhotoBtn.classList.remove('d-none');
        retakePhotoBtn.classList.add('d-none');

        actionButtons.classList.add('d-none');
        photoInfo.classList.add('d-none');

        submitBtn.disabled = true;
        document.getElementById('foto').value = '';
        document.getElementById('lokasi').value = '';

        startCamera();
    });
</script>


<script>
    document.addEventListener('livewire:init', () => {

        Livewire.on('takePhotoAndLocation', async () => {

            const foto = document.getElementById('foto').value;

            if (!foto) {
                alert('Silakan ambil foto terlebih dahulu');
                return;
            }

            // ✅ Ambil lokasi GPS
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lokasi = position.coords.latitude + ',' + position.coords.longitude;

                    // ✅ KIRIM BALIK KE LIVEWIRE
                    Livewire.dispatch('clockInData', {
                        foto: foto,
                        lokasi: lokasi
                    });
                },
                () => {
                    alert('Gagal mengambil lokasi');
                }
            );

        });

    });
</script>
