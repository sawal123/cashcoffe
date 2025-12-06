<div>
    <video id="camera" autoplay class="w-100 rounded"></video>
    <canvas id="canvas" class="d-none"></canvas>

    <div class="d-flex justify-content-center">
        <button class="btn btn-success   mt-3" id="takePhoto">Ambil Foto</button>
    </div>

    <img id="preview" class="mt-3 w-100 rounded d-none">

    <div>
        @if ($absensi && $absensi->status == 'complete')
            <div class="alert alert-success mt-4 text-center">
                <h4><i class="fas fa-check-circle"></i> Anda sudah selesai bekerja hari ini.</h4>
                <p>Jam Masuk: {{ $absensi->jam_masuk }} | Jam Keluar: {{ $absensi->jam_keluar }}</p>
            </div>
        @elseif($absensi && $absensi->jam_masuk && !$absensi->jam_keluar)
            <button class="btn btn-warning mt-4 w-100" wire:click="submitClockIn">
                <i class="fas fa-sign-out-alt"></i> CLOCK OUT (PULANG)
            </button>
        @else
            <button class="btn btn-primary mt-4 w-100" wire:click="submitClockIn">
                <i class="fas fa-sign-in-alt"></i> CLOCK IN (MASUK)
            </button>
        @endif
    </div>
    <input type="hidden" id="foto">
    <input type="hidden" id="lokasi">


    <script>
        document.addEventListener('livewire:init', () => {

            // === AKTIFKAN KAMERA ===
            navigator.mediaDevices.getUserMedia({
                    video: true
                })
                .then(stream => {
                    document.getElementById('camera').srcObject = stream;
                })
                .catch(err => {
                    alert("Kamera tidak dapat diakses!");
                });

            // === AMBIL FOTO + LOKASI SAAT KLIK "AMBIL FOTO" ===
            document.getElementById('takePhoto').onclick = () => {
                const video = document.getElementById('camera');
                const canvas = document.getElementById('canvas');
                const preview = document.getElementById('preview');

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0);

                const imageData = canvas.toDataURL('image/jpeg');

                // tampilkan preview
                preview.src = imageData;
                preview.classList.remove('d-none');

                document.getElementById('foto').value = imageData;

                // lokasi
                navigator.geolocation.getCurrentPosition((pos) => {
                    const lokasi = pos.coords.latitude + "," + pos.coords.longitude;
                    document.getElementById('lokasi').value = lokasi;
                }, () => {
                    alert("Lokasi tidak bisa diambil!");
                });
            };

            // === LIVEWIRE MINTA DATA FOTO & LOKASI ===
            // === LIVEWIRE MINTA DATA FOTO & LOKASI ===
            Livewire.on('takePhotoAndLocation', () => {
                const fotoVal = document.getElementById('foto').value;
                const lokasiVal = document.getElementById('lokasi').value;

                if (!fotoVal || !lokasiVal) {
                    alert("Silakan ambil foto terlebih dahulu dan pastikan lokasi aktif!");
                    return; // Jangan kirim ke server jika kosong
                }

                Livewire.dispatch('clockInData', {
                    foto: fotoVal,
                    lokasi: lokasiVal
                });
            });

        });
    </script>
</div>
