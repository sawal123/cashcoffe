<main class="max-w-[1280px] mx-auto px-4 md:px-8 py-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Time & Action Focus (Bento Style) -->
        <section class="lg:col-span-8 flex flex-col gap-6">
            <!-- Current Time Box -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 md:p-8 shadow-sm flex flex-col items-center justify-center text-center">
                <p class="font-label-md text-label-md text-secondary uppercase tracking-widest mb-2 font-medium">Waktu Sistem Aktual</p>
                <div class="font-display-lg text-display-lg text-primary font-bold flex items-baseline gap-1" id="realtimeAttendanceDisplay">
                    --:--:--
                </div>
                <p class="font-body-lg text-body-lg text-on-surface-variant mt-2 font-medium">{{ now()->translatedFormat('l, d F Y') }}</p>
                <!-- GPS Status Indicator -->
                <div class="mt-6 flex items-center gap-2 px-4 py-1.5 bg-secondary-container rounded-full text-on-secondary-container">
                    <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">location_on</span>
                    <span class="font-label-md text-label-md">GPS Status: <strong class="font-bold">Siap Diverifikasi</strong></span>
                </div>
            </div>

            <!-- Status Hari Ini Info -->
            @if ($absensi && $absensi->jam_masuk && $absensi->jam_keluar)
                <div class="p-4 bg-green-100 text-green-800 rounded-xl text-center font-medium border border-green-200">
                    🎉 Anda telah menyelesaikan jam kerja hari ini.<br>
                    Masuk: <strong class="font-bold">{{ $absensi->jam_masuk }}</strong> | Pulang: <strong class="font-bold">{{ $absensi->jam_keluar }}</strong>
                </div>
            @elseif ($absensi && $absensi->jam_masuk)
                <div class="p-4 bg-blue-50 text-blue-800 rounded-xl text-center font-medium border border-blue-100">
                    Anda sedang dalam status aktif bekerja (Masuk pukul: <strong class="font-bold">{{ $absensi->jam_masuk }}</strong>). Silakan lakukan Clock-out saat jam kerja berakhir.
                </div>
            @endif

            <!-- Action Grid (Clock-in & Clock-out Buttons) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Clock In Button -->
                @if ($absensi && $absensi->jam_masuk)
                    <div class="flex flex-col items-center justify-center gap-3 bg-surface-container-highest text-secondary cursor-not-allowed opacity-60 p-8 rounded-xl h-40 select-none pointer-events-none">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-4xl">login</span>
                        </div>
                        <span class="font-headline-md text-headline-md font-bold">Clock-in</span>
                        <span class="font-label-md text-label-md opacity-90 uppercase tracking-wider">Sudah Tercatat</span>
                    </div>
                @else
                    <a href="{{ url('/absen/verifikasi?type=masuk') }}" wire:navigate class="flex flex-col items-center justify-center gap-3 bg-primary text-on-primary hover:bg-primary-container hover:text-on-primary-container shadow-lg hover:shadow-xl active:scale-95 p-8 rounded-xl transition-all group h-40">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-4xl">login</span>
                        </div>
                        <span class="font-headline-md text-headline-md font-bold">Clock-in</span>
                        <span class="font-label-md text-label-md opacity-90 uppercase tracking-wider">Mulai Jam Kerja</span>
                    </a>
                @endif

                <!-- Clock Out Button -->
                @if (!$absensi || !$absensi->jam_masuk || ($absensi && $absensi->jam_keluar))
                    <div class="flex flex-col items-center justify-center gap-3 bg-surface-container-highest text-secondary cursor-not-allowed opacity-60 p-8 rounded-xl h-40 select-none pointer-events-none">
                        <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-4xl">logout</span>
                        </div>
                        <span class="font-headline-md text-headline-md font-bold">Clock-out</span>
                        <span class="font-label-md text-label-md opacity-90 uppercase tracking-wider">{{ ($absensi && $absensi->jam_keluar) ? 'Selesai Hari Ini' : 'Akhiri Jam Kerja' }}</span>
                    </div>
                @else
                    <a href="{{ url('/absen/verifikasi?type=keluar') }}" wire:navigate class="flex flex-col items-center justify-center gap-3 bg-white border-2 border-primary text-primary hover:bg-surface-container-low shadow-md active:scale-95 p-8 rounded-xl transition-all group h-40">
                        <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-4xl">logout</span>
                        </div>
                        <span class="font-headline-md text-headline-md font-bold">Clock-out</span>
                        <span class="font-label-md text-label-md opacity-90 uppercase tracking-wider">Akhiri Jam Kerja</span>
                    </a>
                @endif
            </div>
        </section>

        <!-- Location & Stats Sidebar -->
        <aside class="lg:col-span-4 flex flex-col gap-6">
            <!-- Peta Lokasi -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm h-full flex flex-col min-h-[300px]">
                <div class="p-4 border-b border-outline-variant flex justify-between items-center bg-surface-bright">
                    <h3 class="font-title-lg text-title-lg text-primary font-bold">Titik Lokasi Kantor</h3>
                    <span class="material-symbols-outlined text-secondary">domain</span>
                </div>
                <div class="relative flex-grow bg-surface-container">
                    @php
                        $userBranch = auth()->user()->branch;
                        $lat = $userBranch->latitude ?? '-6.200000';
                        $lng = $userBranch->longitude ?? '106.816666';
                        $radius = $userBranch->radius ?? 50;
                        $branchName = $userBranch->nama_cabang ?? 'Cabang Utama';
                    @endphp
                    <iframe class="w-full h-full border-0 absolute inset-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q={{ $lat }},{{ $lng }}&hl=id&z=15&output=embed"></iframe>
                </div>
                <div class="p-4 bg-white/90 border-t border-outline-variant flex items-start gap-3">
                    <span class="material-symbols-outlined text-primary mt-1" style="font-variation-settings: 'FILL' 1;">domain</span>
                    <div>
                        <p class="font-label-md text-label-md text-primary font-bold">{{ $branchName }}</p>
                        <p class="font-body-md text-body-md text-on-surface-variant text-xs mt-0.5">Radius presisi diaktifkan ({{ $radius }} Meter)</p>
                    </div>
                </div>
            </div>

            <!-- Weekly Target Summary -->
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 shadow-sm">
                <h3 class="font-title-lg text-title-lg text-primary font-bold mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">track_changes</span>
                    <span>Progres Kehadiran</span>
                </h3>
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between mb-1 text-xs">
                            <span class="text-on-surface-variant font-medium">Target Jam Kerja</span>
                            <span class="text-primary font-bold">40 Jam / Minggu</span>
                        </div>
                        <div class="w-full h-2 bg-surface-container-high rounded-full overflow-hidden">
                            <div class="h-full bg-primary rounded-full w-[85%]"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-outline-variant text-xs font-medium">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                            <span>Status Kinerja</span>
                        </div>
                        <span class="bg-secondary-container text-on-secondary-container px-2 py-0.5 rounded font-bold uppercase text-[10px]">Optimal</span>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setInterval(() => {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                const el = document.getElementById('realtimeAttendanceDisplay');
                if(el) el.innerText = `${hours}:${minutes}:${seconds}`;
            }, 1000);
        });
    </script>
</main>
