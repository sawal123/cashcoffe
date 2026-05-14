<main class="max-w-[1280px] mx-auto px-4 md:px-8 py-6">
    <!-- Header & Filter Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface font-bold">Riwayat Kehadiran</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">Kelola dan pantau catatan waktu kerja Anda.</p>
        </div>
        <div class="flex items-center gap-2 bg-surface-container border border-outline-variant rounded-xl px-4 py-2 shadow-sm">
            <span class="material-symbols-outlined text-secondary">calendar_month</span>
            <select wire:model.live="selectedMonth" class="bg-transparent border-none focus:ring-0 font-button text-button text-primary cursor-pointer appearance-none pr-8 font-medium">
                @foreach ($months as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Attendance Stats Summary (Bento Style) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 flex flex-col items-center justify-center text-center shadow-sm">
            <span class="font-label-md text-label-md text-secondary uppercase tracking-widest mb-1 font-medium">Total Kehadiran</span>
            <span class="font-headline-md text-headline-md text-primary font-bold">{{ $totalKehadiran }} Hari</span>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 flex flex-col items-center justify-center text-center shadow-sm">
            <span class="font-label-md text-label-md text-secondary uppercase tracking-widest mb-1 font-medium">Total Jam Kerja</span>
            <span class="font-headline-md text-headline-md text-primary font-bold">{{ $totalJamKerja }} Jam</span>
        </div>
        <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 flex flex-col items-center justify-center text-center shadow-sm">
            <span class="font-label-md text-label-md text-secondary uppercase tracking-widest mb-1 font-medium">Rata-rata Masuk</span>
            <span class="font-headline-md text-headline-md text-primary font-bold">{{ $avgMasuk }}</span>
        </div>
    </div>

    <!-- Chronological List -->
    <div class="space-y-4">
        @forelse ($records as $r)
            @php
                $tgl = \Carbon\Carbon::parse($r->tanggal);
                $isTerlambat = $r->status === 'terlambat';
                $isIzin = $r->status === 'izin';
                
                $jamKerjaItem = '--h --m';
                if ($r->jam_masuk && $r->jam_keluar) {
                    $m = \Carbon\Carbon::parse($r->jam_masuk);
                    $k = \Carbon\Carbon::parse($r->jam_keluar);
                    $diff = $m->diffInMinutes($k);
                    $jamKerjaItem = floor($diff / 60) . 'h ' . ($diff % 60) . 'm';
                }
            @endphp
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-4 md:p-6 flex flex-col md:flex-row items-start md:items-center justify-between hover:bg-surface-container-low transition-colors shadow-xs group">
                <div class="flex items-center gap-4 w-full md:w-auto mb-4 md:mb-0">
                    <div class="flex flex-col items-center justify-center {{ $isTerlambat ? 'bg-error-container text-on-error-container' : ($isIzin ? 'bg-surface-container-highest text-secondary' : 'bg-primary-container text-on-primary-container') }} w-14 h-14 rounded-xl shrink-0">
                        <span class="font-label-md text-label-md leading-none font-bold">{{ $tgl->translatedFormat('M') }}</span>
                        <span class="font-title-lg text-title-lg leading-none mt-1 font-bold">{{ $tgl->format('d') }}</span>
                    </div>
                    <div>
                        <h3 class="font-title-lg text-title-lg text-on-surface font-semibold">{{ $tgl->translatedFormat('l, d F Y') }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant capitalize">{{ $r->status ?? 'Hadir' }}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-3 md:flex md:items-center gap-6 w-full md:w-auto">
                    <div class="flex flex-col">
                        <span class="font-label-md text-label-md text-secondary">Masuk</span>
                        <span class="font-body-lg text-body-lg font-semibold {{ $isTerlambat ? 'text-error' : 'text-on-surface' }}">{{ $r->jam_masuk ? \Carbon\Carbon::parse($r->jam_masuk)->format('H:i') : '--:--' }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-label-md text-label-md text-secondary">Pulang</span>
                        <span class="font-body-lg text-body-lg font-semibold text-on-surface">{{ $r->jam_keluar ? \Carbon\Carbon::parse($r->jam_keluar)->format('H:i') : '--:--' }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-label-md text-label-md text-secondary">Total</span>
                        <span class="font-body-lg text-body-lg font-semibold text-on-surface">{{ $jamKerjaItem }}</span>
                    </div>
                </div>

                <div class="mt-4 md:mt-0 w-full md:w-32 flex justify-end">
                    @if ($isTerlambat)
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-error-container text-on-error-container text-xs font-medium">
                            Terlambat
                        </span>
                    @elseif ($isIzin)
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-secondary-container text-on-secondary-container text-xs font-medium">
                            Izin
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">
                            Tepat Waktu
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center bg-surface-container-lowest border border-outline-variant rounded-xl">
                <span class="material-symbols-outlined text-secondary text-4xl mb-2">history_toggle_off</span>
                <p class="font-body-md text-body-md text-on-surface-variant">Belum ada catatan kehadiran pada bulan ini.</p>
            </div>
        @endforelse
    </div>
</main>
