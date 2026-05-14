<main class="max-w-[1280px] mx-auto px-4 md:px-8 mt-6">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        <!-- Left Column: Attendance Focus -->
        <div class="md:col-span-8 flex flex-col gap-6">
            
            @if (session()->has('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-xl font-medium flex items-center gap-2">
                    <span class="material-symbols-outlined text-green-700">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Main Action Card (Bento Style) -->
            <section class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 shadow-[0_4px_12px_rgba(0,0,0,0.04)] relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-64 h-64 bg-primary-container/5 rounded-full -mr-20 -mt-20 pointer-events-none"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
                    <div>
                        <p class="font-label-md text-label-md text-secondary uppercase tracking-wider mb-2 font-medium">Status Saat Ini</p>
                        
                        @if (!$absensiToday)
                            <h2 class="font-headline-lg text-headline-lg text-error mb-4 font-bold">Belum Absen</h2>
                        @elseif ($absensiToday && !$absensiToday->jam_keluar)
                            <h2 class="font-headline-lg text-headline-lg text-primary mb-4 font-bold">Sedang Bekerja</h2>
                        @else
                            <h2 class="font-headline-lg text-headline-lg text-green-600 mb-4 font-bold">Selesai Bekerja</h2>
                        @endif

                        <div class="flex items-center gap-4 text-on-surface-variant">
                            <div class="flex flex-col">
                                <span class="font-label-md text-label-md">{{ $today }}</span>
                                <span class="font-title-lg text-title-lg text-on-surface font-semibold" id="liveTimeDashboard">{{ $time }}</span>
                            </div>
                            <div class="h-8 w-[1px] bg-outline-variant"></div>
                            <div class="flex flex-col">
                                <span class="font-label-md text-label-md">Jam Masuk Jadwal</span>
                                <span class="font-title-lg text-title-lg text-on-surface font-semibold">{{ $shift ? $shift->jam_masuk : '08:00:00' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <a href="{{ route('absensi.clock.in') }}" class="bg-primary hover:bg-primary-container text-on-primary hover:text-on-primary-container font-button text-button px-10 py-5 rounded-xl shadow-lg active:scale-95 transition-all flex items-center justify-center gap-3 shrink-0">
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">login</span>
                        <span>{{ !$absensiToday ? 'Absen Sekarang' : (!$absensiToday->jam_keluar ? 'Absen Pulang' : 'Lihat Detail') }}</span>
                    </a>
                </div>
            </section>

            <!-- Daily Summary & Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Work Hours Summary -->
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm">
                    <h3 class="font-title-lg text-title-lg mb-6 flex items-center gap-2 font-bold text-on-surface">
                        <span class="material-symbols-outlined text-primary">timer</span>
                        <span>Jam Kerja Hari Ini</span>
                    </h3>

                    @php
                        $jamKerjaStr = '0h 0m';
                        $percent = 0;
                        if ($absensiToday && $absensiToday->jam_masuk) {
                            $masuk = \Carbon\Carbon::parse($absensiToday->jam_masuk);
                            $keluar = $absensiToday->jam_keluar ? \Carbon\Carbon::parse($absensiToday->jam_keluar) : now();
                            $diffMins = $masuk->diffInMinutes($keluar);
                            $hours = floor($diffMins / 60);
                            $mins = $diffMins % 60;
                            $jamKerjaStr = "{$hours}h {$mins}m";
                            $percent = min(100, ($diffMins / (8 * 60)) * 100);
                        }
                    @endphp

                    <div class="flex items-baseline gap-1 mb-2">
                        <span class="font-display-lg text-display-lg text-primary font-bold">{{ $jamKerjaStr }}</span>
                        <span class="font-body-md text-body-md text-secondary">/ 8h</span>
                    </div>
                    <div class="w-full bg-surface-container h-3 rounded-full overflow-hidden">
                        <div class="bg-primary h-full transition-all duration-500 rounded-full" style="width: {{ $percent }}%;"></div>
                    </div>
                    <p class="mt-4 font-body-md text-body-md text-on-surface-variant">
                        {{ !$absensiToday ? 'Kamu belum mulai bekerja hari ini.' : ($absensiToday->jam_keluar ? 'Kerja hari ini telah selesai.' : 'Semangat menyelesaikan tugas hari ini!') }}
                    </p>
                </div>

                <!-- Weekly Progress -->
                <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 shadow-sm">
                    <h3 class="font-title-lg text-title-lg mb-6 flex items-center gap-2 font-bold text-on-surface">
                        <span class="material-symbols-outlined text-tertiary">analytics</span>
                        <span>Statistik Mingguan</span>
                    </h3>
                    <div class="flex justify-between items-end h-24 gap-2">
                        <div class="flex flex-col items-center gap-2 flex-1">
                            <div class="w-full bg-primary rounded-t-sm h-[80%]"></div>
                            <span class="text-[10px] font-medium text-secondary">S</span>
                        </div>
                        <div class="flex flex-col items-center gap-2 flex-1">
                            <div class="w-full bg-primary rounded-t-sm h-[90%]"></div>
                            <span class="text-[10px] font-medium text-secondary">S</span>
                        </div>
                        <div class="flex flex-col items-center gap-2 flex-1">
                            <div class="w-full bg-primary rounded-t-sm h-[75%]"></div>
                            <span class="text-[10px] font-medium text-secondary">R</span>
                        </div>
                        <div class="flex flex-col items-center gap-2 flex-1">
                            <div class="w-full bg-primary rounded-t-sm h-[85%]"></div>
                            <span class="text-[10px] font-medium text-secondary">K</span>
                        </div>
                        <div class="flex flex-col items-center gap-2 flex-1">
                            <div class="w-full {{ $absensiToday ? 'bg-primary' : 'bg-surface-container' }} rounded-t-sm h-[{{ $absensiToday ? '60%' : '10%' }}]"></div>
                            <span class="text-[10px] font-medium text-secondary">J</span>
                        </div>
                    </div>
                    <p class="mt-4 font-label-md text-label-md text-on-surface-variant text-right">Rata-rata: 7.8 jam/hari</p>
                </div>
            </div>
        </div>

        <!-- Right Column: Contextual Info -->
        <div class="md:col-span-4 flex flex-col gap-6">
            <!-- Office Announcements -->
            <section class="bg-surface-container-high rounded-xl p-6 h-full border border-outline-variant/50">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-title-lg text-title-lg flex items-center gap-2 font-bold text-on-surface">
                        <span class="material-symbols-outlined text-primary">campaign</span>
                        <span>Pengumuman</span>
                    </h3>
                    <span class="material-symbols-outlined text-secondary cursor-pointer">open_in_new</span>
                </div>
                <div class="space-y-4">
                    <div class="bg-surface-container-lowest p-4 rounded-lg border border-outline-variant/30 shadow-xs">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 bg-tertiary-fixed text-on-tertiary-fixed text-[10px] font-bold rounded uppercase">Update</span>
                            <span class="text-[11px] text-secondary">2 jam yang lalu</span>
                        </div>
                        <h4 class="font-button text-button mb-1 font-semibold text-on-surface">Kebijakan Cuti Baru 2026</h4>
                        <p class="font-body-md text-body-md text-on-surface-variant line-clamp-2">Mohon tinjau kebijakan hak cuti tahunan terbaru yang telah diperbarui di sistem HR...</p>
                    </div>
                    <div class="bg-surface-container-lowest p-4 rounded-lg border border-outline-variant/30 shadow-xs">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 bg-secondary-container text-on-secondary-container text-[10px] font-bold rounded uppercase">Event</span>
                            <span class="text-[11px] text-secondary">Kemarin</span>
                        </div>
                        <h4 class="font-button text-button mb-1 font-semibold text-on-surface">Townhall Meeting Bulanan</h4>
                        <p class="font-body-md text-body-md text-on-surface-variant line-clamp-2">Undangan untuk rapat koordinasi operasional bulanan di ruang meeting utama...</p>
                    </div>
                </div>
                <button onclick="alert('Memuat semua pengumuman internal...')" class="w-full mt-6 py-3 border border-outline text-primary font-button text-button font-medium rounded-lg hover:bg-surface-container transition-colors">
                    Lihat Semua Pengumuman
                </button>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setInterval(() => {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const el = document.getElementById('liveTimeDashboard');
                if(el) el.innerText = `${hours}.${minutes} WIB`;
            }, 1000);
        });
    </script>
</main>
