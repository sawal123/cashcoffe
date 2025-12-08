<div>

    {{-- CAMERA --}}
    <video id="camera" autoplay class="w-100 rounded"></video>
    <canvas id="canvas" class="d-none"></canvas>

    {{-- FOTO BUTTON --}}
    <div class="d-flex justify-content-center gap-2 mt-3">
        <button class="btn btn-success" id="takePhoto">Ambil Foto</button>
        <button class="btn btn-secondary d-none" id="retakePhoto">Foto Ulang</button>
    </div>

    {{-- PREVIEW --}}
    <img id="preview" class="mt-3 w-100 rounded d-none">

    {{-- ACTION BUTTON --}}
    <div id="actionButtons" class="text-center mt-4">

        {{-- ✅ SUDAH CLOCK OUT --}}
        @if ($absensi && $absensi->jam_masuk && $absensi->jam_keluar)
        <div class="alert alert-success">
            <strong>Selesai bekerja hari ini</strong><br>
            Masuk: {{ $absensi->jam_masuk }} |
            Pulang: {{ $absensi->jam_keluar }}
        </div>

        {{-- ✅ CLOCK OUT --}}
        @elseif ($absensi && $absensi->jam_masuk)
        <button id="submitBtn" class="btn btn-dark" wire:click="submitClockIn" disabled>
            <i class="fas fa-sign-out-alt"></i> CLOCK OUT
        </button>

        {{-- ✅ CLOCK IN --}}
        @else
        <button id="submitBtn" class="btn btn-primary" wire:click="submitClockIn" disabled>
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

        // ✅ AKTIFKAN tombol submit
        submitBtn.disabled = false;

        stopCamera();
    });

    retakePhotoBtn.addEventListener('click', () => {
        preview.classList.add('d-none');
        video.classList.remove('d-none');

        takePhotoBtn.classList.remove('d-none');
        retakePhotoBtn.classList.add('d-none');

        submitBtn.disabled = true;
        document.getElementById('foto').value = '';

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
