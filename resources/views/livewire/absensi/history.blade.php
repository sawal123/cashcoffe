<main x-data="{ 
    modalOpen: false, 
    modalTitle: '', 
    modalPhoto: '', 
    modalLocation: '', 
    openModal(title, photo, location) {
        this.modalTitle = title;
        this.modalPhoto = photo;
        this.modalLocation = location;
        this.modalOpen = true;
    }
}" class="max-w-[1280px] mx-auto px-4 md:px-8 py-6">
    <style>
        [x-cloak] { display: none !important; }
    </style>

    <!-- Header & Filter Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface font-bold">Riwayat Kehadiran</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">Kelola dan pantau catatan waktu kerja Anda.</p>
        </div>
        <div class="flex items-center gap-2 bg-surface-container border border-outline-variant rounded-xl px-4 py-2 shadow-sm relative pr-2">
            <span class="material-symbols-outlined text-secondary">calendar_month</span>
            <select wire:model.live="selectedMonth" class="bg-transparent border-none focus:ring-0 font-button text-button text-primary cursor-pointer appearance-none bg-none pr-6 font-medium">
                @foreach ($months as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
            <span class="material-symbols-outlined text-primary pointer-events-none text-sm">expand_more</span>
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
                $status = strtolower($r->status ?? 'hadir');
                $isPastDate = $tgl->copy()->startOfDay()->lt(now()->startOfDay());
                $canRequestCorrection = $isPastDate && !in_array($status, ['izin', 'sakit', 'cuti'], true);
                
                $jamKerjaItem = '--h --m';
                if ($r->jam_masuk && $r->jam_keluar) {
                    $m = \Carbon\Carbon::parse($r->jam_masuk);
                    $k = \Carbon\Carbon::parse($r->jam_keluar);
                    $diff = $m->diffInMinutes($k);
                    $jamKerjaItem = floor($diff / 60) . 'h ' . ($diff % 60) . 'm';
                }

                // Map status to classes and badges
                $isTerlambat = $status === 'terlambat';
                switch ($status) {
                    case 'terlambat':
                        $calendarColor = 'bg-error-container text-on-error-container';
                        $statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-error-container text-on-error-container text-xs font-medium">Terlambat</span>';
                        break;
                    case 'izin':
                        $calendarColor = 'bg-secondary-container text-on-secondary-container';
                        $statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-secondary-container text-on-secondary-container text-xs font-medium">Izin</span>';
                        break;
                    case 'cuti':
                        $calendarColor = 'bg-primary-container text-on-primary-container';
                        $statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-primary-container text-on-primary-container text-xs font-medium">Cuti</span>';
                        break;
                    case 'alpha':
                        $calendarColor = 'bg-error text-white';
                        $statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-error/15 text-error text-xs font-medium">Alpha</span>';
                        break;
                    case 'tidak clock out':
                        $calendarColor = 'bg-amber-100 text-amber-850';
                        $statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-medium">Tidak Clock Out</span>';
                        break;
                    case 'hadir':
                    default:
                        $calendarColor = 'bg-green-100 text-green-800';
                        $statusBadge = '<span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">Tepat Waktu</span>';
                        break;
                }
            @endphp
            <div x-data="{ open: false }" class="bg-surface-container-lowest border border-outline-variant rounded-xl hover:shadow-md transition-all shadow-xs group">
                <!-- Accordion Trigger Header -->
                <div @click="open = !open" class="p-4 md:p-6 flex flex-col md:flex-row items-start md:items-center justify-between hover:bg-surface-container-low transition-colors cursor-pointer select-none">
                    <div class="flex items-center gap-4 w-full md:w-auto mb-4 md:mb-0">
                        <div class="flex flex-col items-center justify-center {{ $calendarColor }} w-14 h-14 rounded-xl shrink-0">
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

                    <div class="mt-4 md:mt-0 w-full md:w-auto flex items-center justify-between md:justify-end gap-3 self-stretch md:self-auto">
                        <div>
                            {!! $statusBadge !!}
                        </div>
                        <span class="material-symbols-outlined text-secondary transition-transform duration-300" :class="open ? 'rotate-180' : ''">expand_more</span>
                    </div>
                </div>

                <!-- Accordion Detail Panel -->
                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-y-95"
                     x-transition:enter-end="opacity-100 transform scale-y-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 transform scale-y-100"
                     x-transition:leave-end="opacity-0 transform scale-y-95"
                     class="border-t border-outline-variant/60 bg-surface/30 p-4 md:p-6 space-y-4" 
                     style="display: none;">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Clock In Detail Card -->
                        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-4 flex flex-col justify-between gap-3 shadow-xs">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-title-lg text-title-lg font-semibold text-on-surface">Clock In (Masuk)</h4>
                                    <p class="font-body-md text-body-md text-on-surface-variant mt-0.5">Jam Masuk: <strong class="text-on-surface">{{ $r->jam_masuk ? \Carbon\Carbon::parse($r->jam_masuk)->format('H:i:s') : '--:--:--' }}</strong></p>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-primary-container text-on-primary-container text-[11px] font-semibold uppercase">
                                    Masuk
                                </span>
                            </div>
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-outline-variant/40">
                                <span class="font-label-md text-label-md text-secondary">Foto & GPS terekam</span>
                                @if ($r->jam_masuk)
                                    <button @click.stop="openModal('Detail Clock In - {{ $tgl->translatedFormat('d M Y') }}', '{{ $r->foto ? asset('storage/' . $r->foto) : '' }}', '{{ $r->lokasi ?? '' }}')" 
                                            type="button" 
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-surface-container-high hover:bg-surface-container-highest text-primary font-button text-xs rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                        <span>Lihat Detail</span>
                                    </button>
                                @else
                                    @if ($canRequestCorrection)
                                        <a href="{{ route('absensi.correction', [
                                            'tanggal' => $tgl->toDateString(),
                                            'field' => 'clock_in',
                                        ]) }}"
                                            wire:navigate
                                            @click.stop
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-error-container px-3.5 py-1.5 text-xs font-bold text-on-error-container transition-colors hover:bg-error hover:text-white">
                                            <span class="material-symbols-outlined text-sm">edit_calendar</span>
                                            <span>Perbaiki Clock In</span>
                                        </a>
                                    @else
                                        <span class="text-xs text-secondary italic">Belum absen masuk</span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <!-- Clock Out Detail Card -->
                        <div class="bg-surface-container-lowest border border-outline-variant/60 rounded-xl p-4 flex flex-col justify-between gap-3 shadow-xs">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-title-lg text-title-lg font-semibold text-on-surface">Clock Out (Pulang)</h4>
                                    <p class="font-body-md text-body-md text-on-surface-variant mt-0.5">Jam Pulang: <strong class="text-on-surface">{{ $r->jam_keluar ? \Carbon\Carbon::parse($r->jam_keluar)->format('H:i:s') : '--:--:--' }}</strong></p>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container text-[11px] font-semibold uppercase">
                                    Pulang
                                </span>
                            </div>
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-outline-variant/40">
                                <span class="font-label-md text-label-md text-secondary">Foto & GPS terekam</span>
                                @if ($r->jam_keluar)
                                    <button @click.stop="openModal('Detail Clock Out - {{ $tgl->translatedFormat('d M Y') }}', '{{ $r->foto_keluar ? asset('storage/' . $r->foto_keluar) : '' }}', '{{ $r->lokasi_keluar ?? '' }}')" 
                                            type="button" 
                                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-surface-container-high hover:bg-surface-container-highest text-primary font-button text-xs rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                        <span>Lihat Detail</span>
                                    </button>
                                @else
                                    @if ($canRequestCorrection)
                                        <a href="{{ route('absensi.correction', [
                                            'tanggal' => $tgl->toDateString(),
                                            'field' => 'clock_out',
                                        ]) }}"
                                            wire:navigate
                                            @click.stop
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-error-container px-3.5 py-1.5 text-xs font-bold text-on-error-container transition-colors hover:bg-error hover:text-white">
                                            <span class="material-symbols-outlined text-sm">edit_calendar</span>
                                            <span>Perbaiki Clock Out</span>
                                        </a>
                                    @else
                                        <span class="text-xs text-secondary italic">Belum absen pulang</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-8 text-center bg-surface-container-lowest border border-outline-variant rounded-xl">
                <span class="material-symbols-outlined text-secondary text-4xl mb-2">history_toggle_off</span>
                <p class="font-body-md text-body-md text-on-surface-variant">Belum ada catatan kehadiran pada bulan ini.</p>
            </div>
        @endforelse
    </div>

    <!-- Modal Detail Absensi -->
    <div x-show="modalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-cloak>
        <!-- Backdrop -->
        <div x-show="modalOpen" 
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="modalOpen = false" 
             class="fixed inset-0 bg-black/60 backdrop-blur-xs"></div>
        
        <!-- Modal Body -->
        <div x-show="modalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="bg-surface-container-lowest dark:bg-surface-dim rounded-2xl overflow-hidden shadow-2xl border border-outline-variant max-w-lg w-full z-10 flex flex-col max-h-[90vh]">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-outline-variant/60 flex justify-between items-center bg-surface">
                <h3 class="font-title-lg text-title-lg text-on-surface font-bold" x-text="modalTitle">Detail Absensi</h3>
                <button @click="modalOpen = false" class="text-secondary hover:text-on-surface p-1 rounded-full hover:bg-surface-container-high transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <!-- Modal Content -->
            <div class="p-6 overflow-y-auto space-y-6 flex-grow">
                <!-- Photo -->
                <div class="space-y-2">
                    <h4 class="font-label-md text-label-md text-secondary uppercase font-semibold">Foto Absensi</h4>
                    <div class="aspect-video w-full rounded-xl overflow-hidden bg-surface-container border border-outline-variant flex items-center justify-center relative">
                        <template x-if="modalPhoto">
                            <img :src="modalPhoto" alt="Foto Absensi" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!modalPhoto">
                            <div class="flex flex-col items-center justify-center text-secondary">
                                <span class="material-symbols-outlined text-4xl mb-1">image_not_supported</span>
                                <span class="text-xs">Foto tidak tersedia</span>
                            </div>
                        </template>
                    </div>
                </div>
                
                <!-- Location / Map -->
                <div class="space-y-2">
                    <h4 class="font-label-md text-label-md text-secondary uppercase font-semibold">Lokasi Absen</h4>
                    
                    <!-- Map Iframe Embed -->
                    <div class="aspect-video w-full rounded-xl overflow-hidden border border-outline-variant/60 bg-surface-container relative shadow-sm">
                        <template x-if="modalLocation">
                            <iframe 
                                class="w-full h-full border-0"
                                :src="'https://maps.google.com/maps?q=' + encodeURIComponent(modalLocation) + '&z=16&output=embed'"
                                allowfullscreen="" 
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </template>
                        <template x-if="!modalLocation">
                            <div class="flex flex-col items-center justify-center h-full text-secondary">
                                <span class="material-symbols-outlined text-4xl mb-1 font-light">location_off</span>
                                <span class="text-xs">Lokasi tidak tersedia</span>
                            </div>
                        </template>
                    </div>

                    <!-- Coordinates Card -->
                    <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/60 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-primary-container/20 p-2 rounded-lg text-primary">
                                <span class="material-symbols-outlined">location_on</span>
                            </div>
                            <div>
                                <p class="font-body-md text-body-md font-semibold text-on-surface break-all" x-text="modalLocation || 'Lokasi tidak terekam'"></p>
                                <p class="font-label-md text-label-md text-secondary">Koordinat GPS</p>
                            </div>
                        </div>
                        <template x-if="modalLocation">
                            <a :href="'https://www.google.com/maps/search/?api=1&query=' + modalLocation" 
                               target="_blank" 
                               class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary text-on-primary font-button text-button rounded-xl hover:bg-primary-container active:scale-[0.98] transition-all shrink-0">
                                <span class="material-symbols-outlined text-sm">map</span>
                                <span>Buka Google Maps</span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
