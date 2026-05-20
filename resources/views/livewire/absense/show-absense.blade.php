<div>
    <x-toast />

    <style>
        .compact-card {
            padding: 0.75rem !important;
            border-radius: 1.25rem !important;
            gap: 0.5rem !important;
        }

        .compact-card>div {
            gap: 0.5rem !important;
        }

        .compact-card .w-10 {
            width: 2rem !important;
            height: 2rem !important;
            border-radius: 0.75rem !important;
        }

        .compact-card iconify-icon {
            font-size: 1rem !important;
        }

        .compact-card h3 {
            font-size: 1.25rem !important;
        }
    </style>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">Rekap Absensi Karyawan</h6>
            <p class="text-xs text-neutral-400 font-bold uppercase tracking-wider mt-1">{{ $user->name }} &bull;
                {{ $user->email }}</p>
        </div>
        <x-breadcrumb :title="$user->name" />
    </div>

    {{-- SUMMARY STATS --}}
    <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-3 mb-8">
        <x-ui.card title="Complete" :value="$totalHadir" icon="lucide:user-check" color="white" class="compact-card" />
        <x-ui.card title="Terlambat" :value="$totalTerlambat" icon="lucide:clock" color="white" class="compact-card" />
        <x-ui.card title="Izin" :value="$totalIzin" icon="lucide:file-text" color="white" class="compact-card" />
        <x-ui.card title="Sakit" :value="$totalSakit" icon="lucide:activity" color="white" class="compact-card" />
        <x-ui.card title="Cuti" :value="$totalCuti" icon="lucide:calendar-range" color="white" class="compact-card" />
        <x-ui.card title="Alpha" :value="$totalAlpha" icon="lucide:user-x" color="white" class="compact-card" />
        <x-ui.card title="Lupa Clock Out" :value="$totalTidakClockOut" icon="lucide:alert-circle" color="white"
            class="compact-card" />
    </div>

    {{-- FILTER BAR --}}
    <div
        class="bg-white dark:bg-neutral-800/50 rounded-3xl p-4 mb-8 flex flex-wrap items-center gap-4 shadow-sm border border-neutral-100 dark:border-neutral-700">
        <div class="w-44">
            <x-ui.select wire:model.live="month">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </x-ui.select>
        </div>
        <div class="w-32">
            <x-ui.select wire:model.live="year">
                @foreach (range(now()->year - 2, now()->year) as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </x-ui.select>
        </div>
    </div>

    {{-- TABLE --}}
    <x-ui.table :headers="['#', 'Tanggal', 'Masuk', 'Keluar', 'Status', ['name' => 'Aksi', 'align' => 'center']]">
        @forelse ($calendar as $i => $row)
            @php
                $item = $row['absen'];
                $tanggal = \Carbon\Carbon::parse($row['tanggal']);

                $terlambat = false;
                if ($item && $item->jam_masuk && $shift) {
                    $terlambat = \Carbon\Carbon::parse($item->jam_masuk)->gt(\Carbon\Carbon::parse($shift->jam_masuk));
                }
            @endphp

            <tr
                class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/30 transition-colors group {{ $item ? '' : 'opacity-70' }}">
                <td data-label="#" class="px-6 py-5 text-xs font-bold text-neutral-300">
                    {{ $i + 1 }}
                </td>
                <td data-label="Tanggal" class="px-6 py-5 font-bold text-neutral-800 dark:text-neutral-100">
                    {{ $tanggal->translatedFormat('d M Y') }}
                </td>
                <td data-label="Masuk" class="px-6 py-5">
                    @if ($item?->jam_masuk)
                        <div class="flex items-center gap-2">
                            <iconify-icon icon="lucide:log-in" class="text-emerald-500 text-xs"></iconify-icon>
                            <span
                                class="text-sm font-bold {{ $terlambat ? 'text-danger-600' : 'text-neutral-700 dark:text-neutral-300' }}">
                                {{ $item->jam_masuk }}
                            </span>
                        </div>
                    @else
                        <span class="text-neutral-300">--:--</span>
                    @endif
                </td>
                <td data-label="Keluar" class="px-6 py-5">
                    @if ($item?->jam_keluar)
                        <div class="flex items-center gap-2">
                            <iconify-icon icon="lucide:log-out" class="text-rose-500 text-xs"></iconify-icon>
                            <span class="text-sm font-bold text-neutral-700 dark:text-neutral-300">
                                {{ $item->jam_keluar }}
                            </span>
                        </div>
                    @else
                        <span class="text-neutral-300">--:--</span>
                    @endif
                </td>
                <td data-label="Status" class="px-6 py-5">
                    @php
                        $isToday = $tanggal->isToday();
                        $isPast = $tanggal->isPast();
                    @endphp

                    @if ($item)
                        @php
                            $statusColors = match ($item->status) {
                                'hadir',
                                'complete'
                                    => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
                                'terlambat' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
                                'tidak clock out'
                                    => 'bg-orange-50 text-orange-600 dark:bg-orange-950/30 dark:text-orange-400 border border-orange-200 dark:border-orange-900/50',
                                'alpha' => 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400',
                                'izin',
                                'sakit',
                                'cuti'
                                    => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400',
                                'wfh',
                                'dinas_luar'
                                    => 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
                                default => 'bg-neutral-50 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
                            };
                        @endphp
                        <span
                            class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $statusColors }}">
                            {{ $item->status }}
                        </span>
                    @else
                        @if ($isPast)
                            <span
                                class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400">
                                Alpha
                            </span>
                        @elseif ($isToday)
                            <span
                                class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                                Belum Absen
                            </span>
                        @else
                            <span class="text-neutral-300">—</span>
                        @endif
                    @endif
                </td>
                <td data-label="Aksi" class="px-6 py-5 text-center">
                    <div class="flex justify-center gap-1.5">
                        @if ($item)
                            <button type="button"
                                @click="$dispatch('open-modal', { name: 'detail-absense', id: {{ $item->id }} })"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-500 transition-all shadow-sm"
                                title="Detail">
                                <iconify-icon icon="lucide:eye" class="text-sm"></iconify-icon>
                            </button>

                            <button type="button"
                                @click="$dispatch('open-modal', { name: 'update-status', id: {{ $item->id }} })"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 hover:bg-amber-600 hover:text-white dark:hover:bg-amber-500 transition-all shadow-sm"
                                title="Update Status">
                                <iconify-icon icon="lucide:edit-3" class="text-sm"></iconify-icon>
                            </button>
                        @else
                            <span class="text-xs text-neutral-300 italic">—</span>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-20">
                    <div class="flex flex-col items-center">
                        <iconify-icon icon="lucide:calendar-x" class="text-5xl text-neutral-200 mb-2"></iconify-icon>
                        <p class="text-sm font-bold text-neutral-400 tracking-wide">Tidak ada data absensi</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    {{-- MODAL DETAIL ABSEN --}}
    <x-mdal name="detail-absense">
        <div class="px-6 py-4" x-data
            @open-modal.window="
            if ($event.detail.name === 'detail-absense') {
                $wire.loadAbsenseDetail($event.detail.id);
            }
        ">
            <h3 class="font-bold text-lg text-center mb-6 text-neutral-900 dark:text-neutral-100">
                Detail Absensi Karyawan
            </h3>

            <div wire:loading class="w-full text-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
                <p class="text-xs text-neutral-400 mt-2">Memuat detail absensi...</p>
            </div>

            <div wire:loading.remove class="space-y-4">
                @if ($selected)
                    {{-- Clock In --}}
                    <div
                        class="p-4 bg-neutral-50 dark:bg-neutral-900/50 rounded-2xl border border-neutral-100 dark:border-neutral-800/50 space-y-3">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Clock
                                In</span>
                            <span class="text-sm font-bold text-neutral-800 dark:text-neutral-100">
                                {{ $selected->jam_masuk ?? '--:--' }}
                            </span>
                        </div>

                        @php
                            $lat = $lng = null;
                            if ($selected->lokasi && str_contains($selected->lokasi, ',')) {
                                [$lat, $lng] = explode(',', $selected->lokasi);
                            }
                        @endphp

                        @if ($lat && $lng)
                            <div
                                class="rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden h-36 relative">
                                <iframe class="w-full h-full border-0 absolute inset-0" loading="lazy"
                                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $lng - 0.005 }},{{ $lat - 0.005 }},{{ $lng + 0.005 }},{{ $lat + 0.005 }}&layer=mapnik&marker={{ $lat }},{{ $lng }}">
                                </iframe>
                            </div>
                            <p class="text-[10px] text-neutral-400 italic">
                                {{ $alamatMasuk ?? 'Lokasi tidak ditemukan' }}
                            </p>
                        @else
                            <div
                                class="h-10 flex items-center justify-center border border-dashed border-neutral-200 dark:border-neutral-700 rounded-xl bg-neutral-50 dark:bg-neutral-800/30">
                                <span class="text-xs text-neutral-400">Lokasi tidak tersedia</span>
                            </div>
                        @endif

                        @if ($selected->foto)
                            <div
                                class="relative rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden h-36">
                                <img src="{{ Storage::url($selected->foto) }}" class="w-full h-full object-cover">
                            </div>
                        @endif
                    </div>

                    {{-- Clock Out --}}
                    <div
                        class="p-4 bg-neutral-50 dark:bg-neutral-900/50 rounded-2xl border border-neutral-100 dark:border-neutral-800/50 space-y-3">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-bold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Clock
                                Out</span>
                            <span class="text-sm font-bold text-neutral-800 dark:text-neutral-100">
                                {{ $selected->jam_keluar ?? 'Belum Clock Out' }}
                            </span>
                        </div>

                        @php
                            $latOut = $lngOut = null;
                            if ($selected->lokasi_keluar && str_contains($selected->lokasi_keluar, ',')) {
                                [$latOut, $lngOut] = explode(',', $selected->lokasi_keluar);
                            }
                        @endphp

                        @if ($latOut && $lngOut)
                            <div
                                class="rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden h-36 relative">
                                <iframe class="w-full h-full border-0 absolute inset-0" loading="lazy"
                                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $lngOut - 0.005 }},{{ $latOut - 0.005 }},{{ $lngOut + 0.005 }},{{ $latOut + 0.005 }}&layer=mapnik&marker={{ $latOut }},{{ $lngOut }}">
                                </iframe>
                            </div>
                            <p class="text-[10px] text-neutral-400 italic">
                                {{ $alamatKeluar ?? 'Lokasi tidak ditemukan' }}
                            </p>
                        @else
                            <div
                                class="h-10 flex items-center justify-center border border-dashed border-neutral-200 dark:border-neutral-700 rounded-xl bg-neutral-50 dark:bg-neutral-800/30">
                                <span class="text-xs text-neutral-400">Belum clock out / lokasi tidak tersedia</span>
                            </div>
                        @endif

                        @if ($selected->foto_keluar)
                            <div
                                class="relative rounded-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden h-36">
                                <img src="{{ Storage::url($selected->foto_keluar) }}"
                                    class="w-full h-full object-cover">
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="mt-6 flex justify-end pt-4 border-t border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="modalIsOpen = false"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-700 text-sm font-bold transition shadow-sm">
                    Tutup
                </button>
            </div>
        </div>
    </x-mdal>

    {{-- MODAL UPDATE STATUS --}}
    <x-mdal name="update-status">
        <div class="px-6 py-4" x-data
            @open-modal.window="
            if ($event.detail.name === 'update-status') {
                $wire.loadStatus($event.detail.id);
            }
        ">
            <h3 class="font-bold text-lg text-center mb-6 text-neutral-900 dark:text-neutral-100">
                Update Status Absensi
            </h3>

            <div class="space-y-4">
                <x-ui.select label="Status Kehadiran" wire:model="status">
                    <option value="">-- Pilih Status --</option>
                    <option value="hadir">Hadir</option>
                    <option value="terlambat">Terlambat</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="cuti">Cuti</option>
                    <option value="alpha">Alpha</option>
                    <option value="tidak clock out">Tidak Clock Out</option>
                    <option value="wfh">WFH</option>
                    <option value="dinas_luar">Dinas Luar</option>
                    <option value="complete">Complete</option>
                </x-ui.select>

                <x-ui.textarea label="Keterangan (Opsional)" wire:model="keterangan" rows="3"
                    placeholder="Masukkan keterangan..." />
            </div>

            <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="modalIsOpen = false"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-700 text-sm font-bold transition shadow-sm">
                    Batal
                </button>

                <x-ui.button type="button" color="blue" wire:click="updateStatus" class="!px-5 !py-2.5">
                    <iconify-icon icon="mingcute:check-line" class="mr-1"></iconify-icon> Simpan
                </x-ui.button>
            </div>
        </div>
    </x-mdal>
</div>
