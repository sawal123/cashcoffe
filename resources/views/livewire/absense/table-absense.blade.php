<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Absensi' }}</h6>
        <x-breadcrumb :title="$title ?? 'Absensi'" />
    </div>

    <x-toast />

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-8">
        {{-- Total Karyawan --}}
        <div class="bg-white dark:bg-neutral-800 rounded-3xl p-6 shadow-sm border border-neutral-100 dark:border-neutral-700">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center">
                    <iconify-icon icon="lucide:users" class="text-2xl"></iconify-icon>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1">Total Karyawan</h4>
                    <p class="text-2xl font-black text-neutral-800 dark:text-neutral-100">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        {{-- Hadir --}}
        <div class="bg-white dark:bg-neutral-800 rounded-3xl p-6 shadow-sm border border-neutral-100 dark:border-neutral-700">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center">
                    <iconify-icon icon="lucide:user-check" class="text-2xl"></iconify-icon>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1">Hadir</h4>
                    <p class="text-2xl font-black text-emerald-600 dark:text-emerald-500">{{ $stats['hadir'] }}</p>
                </div>
            </div>
        </div>

        {{-- Terlambat --}}
        <div class="bg-white dark:bg-neutral-800 rounded-3xl p-6 shadow-sm border border-neutral-100 dark:border-neutral-700">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 flex items-center justify-center">
                    <iconify-icon icon="lucide:clock" class="text-2xl"></iconify-icon>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1">Terlambat</h4>
                    <p class="text-2xl font-black text-amber-600 dark:text-amber-500">{{ $stats['terlambat'] }}</p>
                </div>
            </div>
        </div>

        {{-- Belum Absen --}}
        <div class="bg-white dark:bg-neutral-800 rounded-3xl p-6 shadow-sm border border-neutral-100 dark:border-neutral-700">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 flex items-center justify-center">
                    <iconify-icon icon="lucide:user-x" class="text-2xl"></iconify-icon>
                </div>
                <div>
                    <h4 class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest mb-1">Belum Absen</h4>
                    <p class="text-2xl font-black text-rose-600 dark:text-rose-500">{{ $stats['belum'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white dark:bg-neutral-800/50 rounded-3xl p-4 mb-8 flex flex-wrap items-center gap-4 shadow-sm border border-neutral-100 dark:border-neutral-700">
        <div class="flex-1 min-w-[300px]">
            <x-ui.input prefix="lucide:search" placeholder="Cari nama karyawan..." wire:model.live="search" />
        </div>
        <div class="w-32">
            <x-ui.select wire:model.live="perPage">
                <option value="10">10 Baris</option>
                <option value="25">25 Baris</option>
                <option value="50">50 Baris</option>
            </x-ui.select>
        </div>
    </div>

    {{-- Professional Table --}}
    <x-ui.table :headers="['#', 'Karyawan', 'Status Hari Ini', 'Jam Masuk', 'Jam Keluar', ['name' => 'Aksi', 'align' => 'center']]">
        @forelse ($users as $user)
            @php
    $absen = $user->absensis->first();
            @endphp
            <tr wire:key="row-karyawan-{{ $user->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/30 transition-colors group">
                <td data-label="#" class="px-6 py-5 text-xs font-bold text-neutral-300">
                    {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                </td>
                <td data-label="Karyawan" class="px-6 py-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-neutral-100 dark:bg-neutral-700 flex items-center justify-center font-bold text-neutral-500 uppercase">
                            {{ substr($user->name, 0, 2) }}
                        </div>
                        <div>
                            <span class="block text-sm font-bold text-neutral-800 dark:text-neutral-100">{{ $user->name }}</span>
                            <span class="block text-[10px] text-neutral-400 font-bold uppercase tracking-widest">
                                {{ $user->roles->first()->name ?? 'Staff' }}
                            </span>
                        </div>
                    </div>
                </td>
                <td data-label="Status" class="px-6 py-5">
                    @if ($absen)
                        @php
        $statusColors = match ($absen->status) {
            'hadir' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
            'terlambat' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
            default => 'bg-neutral-50 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400'
        };
                        @endphp
                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $statusColors }}">
                            {{ $absen->status }}
                        </span>
                    @else
                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400">
                            Belum Absen
                        </span>
                    @endif
                </td>
                <td data-label="Jam Masuk" class="px-6 py-5">
                    @if($absen?->jam_masuk)
                        <div class="flex items-center gap-2">
                            <iconify-icon icon="lucide:log-in" class="text-emerald-500 text-xs"></iconify-icon>
                            <span class="text-sm font-bold text-neutral-700 dark:text-neutral-300">{{ $absen->jam_masuk }}</span>
                        </div>
                    @else
                        <span class="text-neutral-300">--:--</span>
                    @endif
                </td>
                <td data-label="Jam Keluar" class="px-6 py-5">
                    @if($absen?->jam_keluar)
                        <div class="flex items-center gap-2">
                            <iconify-icon icon="lucide:log-out" class="text-rose-500 text-xs"></iconify-icon>
                            <span class="text-sm font-bold text-neutral-700 dark:text-neutral-300">{{ $absen->jam_keluar }}</span>
                        </div>
                    @else
                        <span class="text-neutral-300">--:--</span>
                    @endif
                </td>
                <td data-label="Aksi" class="px-6 py-5 text-center">
                    <div class="flex justify-center gap-1.5">
                        {{-- 1. Tombol Tambah Riwayat Absen Hari Ini --}}
                        <button type="button" wire:click="openTambahAbsen({{ $user->id }})"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-600 hover:text-white dark:hover:bg-emerald-500 transition-all shadow-sm"
                            title="Tambah Absen Hari Ini">
                            <iconify-icon icon="lucide:plus" class="text-sm"></iconify-icon>
                        </button>

                        {{-- 2. Tombol Lihat Rekap Bulanan Karyawan --}}
                        <a href="{{ route('absense.show', $user->id) }}" wire:navigate
                            class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-500 transition-all shadow-sm"
                            title="Detail Rekap Bulanan">
                            <iconify-icon icon="lucide:calendar" class="text-sm"></iconify-icon>
                        </a>

                        @if ($absen)
                            {{-- 3. Tombol Detail Absen Hari Ini (Modal Read-Only) --}}
                            <button type="button" wire:click="showDetail({{ $user->id }})"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-500 transition-all shadow-sm"
                                title="Detail Absen Hari Ini (Popup)">
                                <iconify-icon icon="lucide:eye" class="text-sm"></iconify-icon>
                            </button>

                            {{-- 4. Tombol Edit/Hapus Absen Hari Ini (Admin Modal) --}}
                            <button type="button" wire:click="openDetailHariIni({{ $absen->id }})"
                                class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 hover:bg-amber-600 hover:text-white dark:hover:bg-amber-500 transition-all shadow-sm"
                                title="Edit/Hapus Absen Hari Ini">
                                <iconify-icon icon="lucide:edit-3" class="text-sm"></iconify-icon>
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-20">
                    <div class="flex flex-col items-center">
                        <iconify-icon icon="lucide:user-search" class="text-5xl text-neutral-200 mb-2"></iconify-icon>
                        <p class="text-sm font-bold text-neutral-400 tracking-wide">Data karyawan tidak ditemukan</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-6">
        {{ $users->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    {{-- Modal Tambah Absen Hari Ini --}}
    <x-mdal name="modal-tambah-absen">
        <div class="px-6 py-4">
            <h3 class="font-bold text-lg text-center mb-1 text-neutral-900 dark:text-neutral-100">
                Tambah Riwayat Absen Hari Ini
            </h3>
            <p class="text-neutral-500 text-sm text-center mb-6">
                Masukkan data absensi manual untuk karyawan <strong class="text-neutral-800 dark:text-neutral-200">{{ $selectedUserName }}</strong>.
            </p>

            <form wire:submit.prevent="storeManualAbsen">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Shift</label>
                        <select wire:model="shiftId"
                            class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all">
                            @foreach($shifts as $sh)
                                <option value="{{ $sh->id }}">{{ $sh->nama_shift }} ({{ \Carbon\Carbon::parse($sh->jam_masuk)->format('H:i') }} - {{ \Carbon\Carbon::parse($sh->jam_keluar)->format('H:i') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Status</label>
                        <select wire:model="status"
                            class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all">
                            <option value="hadir">Hadir</option>
                            <option value="terlambat">Terlambat</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="cuti">Cuti</option>
                            <option value="alpha">Alpha</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Jam Masuk</label>
                            <input type="time" wire:model="jamMasuk"
                                class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Jam Keluar</label>
                            <input type="time" wire:model="jamKeluar"
                                class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Keterangan / Catatan</label>
                        <textarea wire:model="keterangan" rows="3" placeholder="Alasan manual absen..."
                            class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all"></textarea>
                    </div>
                </div>

                <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-neutral-100 dark:border-neutral-700">
                    <button type="button" x-on:click="$dispatch('close-modal', { name: 'modal-tambah-absen' })"
                        class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-700 text-sm font-bold transition">
                        Batal
                    </button>

                    <x-ui.button type="submit" color="blue" class="!px-5 !py-2.5">
                        <iconify-icon icon="mingcute:check-line" class="mr-1"></iconify-icon> Simpan
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-mdal>

    {{-- Modal Detail & Edit Absen Hari Ini --}}
    <x-mdal name="modal-detail-absen">
        <div class="px-6 py-4">
            <h3 class="font-bold text-lg text-center mb-1 text-neutral-900 dark:text-neutral-100">
                Detail & Edit Absensi Hari Ini
            </h3>
            <p class="text-neutral-500 text-sm text-center mb-6">
                Informasi absensi karyawan <strong class="text-neutral-800 dark:text-neutral-200">{{ $selectedUserName }}</strong>.
            </p>

            <form wire:submit.prevent="updateManualAbsen">
                <div class="space-y-4">
                    @if ($fotoUrl)
                        <div class="flex flex-col items-center mb-4">
                            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Foto Selfie</label>
                            <img src="{{ $fotoUrl }}" alt="Selfie" class="w-32 h-32 object-cover rounded-2xl border-2 border-neutral-200 dark:border-neutral-700 shadow-sm">
                        </div>
                    @endif

                    @if ($lokasiStr)
                        <div>
                            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Lokasi Map</label>
                            <div class="w-full h-32 rounded-xl overflow-hidden border border-neutral-200 dark:border-neutral-700 relative">
                                @php
                                    $coords = explode(',', $lokasiStr);
                                    $lat = trim($coords[0] ?? '-6.200000');
                                    $lng = trim($coords[1] ?? '106.816666');
                                @endphp
                                <iframe class="w-full h-full border-0 absolute inset-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q={{ $lat }},{{ $lng }}&hl=id&z=15&output=embed"></iframe>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Shift</label>
                        <select wire:model="shiftId"
                            class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all">
                            @foreach($shifts as $sh)
                                <option value="{{ $sh->id }}">{{ $sh->nama_shift }} ({{ \Carbon\Carbon::parse($sh->jam_masuk)->format('H:i') }} - {{ \Carbon\Carbon::parse($sh->jam_keluar)->format('H:i') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Status</label>
                        <select wire:model="status"
                            class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all">
                            <option value="hadir">Hadir</option>
                            <option value="terlambat">Terlambat</option>
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="cuti">Cuti</option>
                            <option value="alpha">Alpha</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Jam Masuk</label>
                            <input type="time" wire:model="jamMasuk"
                                class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Jam Keluar</label>
                            <input type="time" wire:model="jamKeluar"
                                class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Keterangan / Catatan</label>
                        <textarea wire:model="keterangan" rows="3" placeholder="Alasan perubahan status..."
                            class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all"></textarea>
                    </div>
                </div>

                <div class="mt-8 flex justify-between items-center pt-4 border-t border-neutral-100 dark:border-neutral-700">
                    <button type="button" wire:click="deleteManualAbsen" wire:confirm="Apakah Anda yakin ingin menghapus absensi hari ini?"
                        class="px-4 py-2.5 rounded-2xl border border-red-300 bg-red-50 hover:bg-red-100 text-red-700 text-sm font-bold transition flex items-center gap-1.5">
                        <iconify-icon icon="lucide:trash-2" class="text-sm"></iconify-icon> Hapus Absen
                    </button>

                    <div class="flex gap-3">
                        <button type="button" x-on:click="$dispatch('close-modal', { name: 'modal-detail-absen' })"
                            class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-700 text-sm font-bold transition">
                            Batal
                        </button>

                        <x-ui.button type="submit" color="blue" class="!px-5 !py-2.5">
                            <iconify-icon icon="mingcute:check-line" class="mr-1"></iconify-icon> Simpan
                        </x-ui.button>
                    </div>
                </div>
            </form>
        </div>
    </x-mdal>

    {{-- Modal Detail Absen Hari Ini (Read-Only Detail Modal) --}}
    @if($showDetailModal && $selectedAbsenDetail)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/60 backdrop-blur-sm transition-all duration-300">
            <div class="bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-100 dark:border-neutral-800 shadow-2xl w-full max-w-md overflow-hidden transform transition-all scale-100">
                
                {{-- Modal Header --}}
                <div class="px-6 py-4 border-b border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/50 flex justify-between items-center">
                    <h3 class="text-base font-bold text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
                        <iconify-icon icon="lucide:info" class="text-primary text-lg"></iconify-icon>
                        Detail Absensi Hari Ini
                    </h3>
                    <button type="button" wire:click="closeDetailModal" class="text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200 transition-colors">
                        <iconify-icon icon="lucide:x" class="text-xl"></iconify-icon>
                    </button>
                </div>
                
                {{-- Modal Body --}}
                <div class="px-6 py-5 space-y-4 text-sm text-neutral-600 dark:text-neutral-400">
                    @php
                        $activeShift = $selectedAbsenDetail->userShift->shift ?? $selectedAbsenDetail->shift;
                    @endphp
                    
                    {{-- User Profile Card --}}
                    <div class="flex items-center gap-3 p-3 bg-neutral-50 dark:bg-neutral-800/30 rounded-2xl border border-neutral-100 dark:border-neutral-800/50">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center font-bold text-primary text-sm uppercase">
                            {{ substr($selectedAbsenDetail->user->name, 0, 2) }}
                        </div>
                        <div>
                            <span class="block font-bold text-neutral-800 dark:text-neutral-100">{{ $selectedAbsenDetail->user->name }}</span>
                            <span class="block text-xs text-neutral-400 font-medium mb-1">
                                {{ $selectedAbsenDetail->user->jabatan->nama_jabatan ?? $selectedAbsenDetail->user->roles->first()->name ?? 'Karyawan' }}
                            </span>
                            <span class="inline-flex px-2 py-0.5 rounded bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 text-[10px] font-bold">
                                {{ \Carbon\Carbon::parse($selectedAbsenDetail->created_at)->format('d M Y') }}
                            </span>
                        </div>
                    </div>

                    {{-- Shift Schedule Info --}}
                    <div>
                        <span class="block text-xs font-bold text-neutral-400 uppercase tracking-wider mb-1.5">Jadwal Shift Hari Ini</span>
                        <div class="p-3 bg-blue-50/50 dark:bg-blue-950/20 text-blue-800 dark:text-blue-300 rounded-2xl border border-blue-100/50 dark:border-blue-900/30 text-xs font-semibold">
                            <iconify-icon icon="lucide:clock" class="inline-block mr-1 text-sm align-middle"></iconify-icon>
                            {{ $activeShift->nama_shift ?? 'Shift Default' }} 
                            ({{ $activeShift ? \Carbon\Carbon::parse($activeShift->jam_masuk)->format('H:i') : '00:00' }} - 
                             {{ $activeShift ? \Carbon\Carbon::parse($activeShift->jam_keluar)->format('H:i') : '00:00' }})
                        </div>
                    </div>

                    <div class="border-t border-neutral-100 dark:border-neutral-800/60 my-4"></div>

                    {{-- Clock-In / Jam Masuk --}}
                    <div>
                        <span class="block text-xs font-bold text-neutral-400 uppercase tracking-wider mb-2">Waktu Realita Lapangan</span>
                        
                        <div class="space-y-3">
                            {{-- Clock In Details --}}
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-lg bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                        <iconify-icon icon="lucide:log-in" class="text-sm"></iconify-icon>
                                    </div>
                                    <span class="font-medium text-neutral-700 dark:text-neutral-300">Jam Masuk</span>
                                </div>
                                <div class="text-right">
                                    <span class="block font-bold text-neutral-900 dark:text-neutral-100">
                                        {{ $selectedAbsenDetail->jam_masuk ? \Carbon\Carbon::parse($selectedAbsenDetail->jam_masuk)->format('H:i:s') : '--:--:--' }}
                                    </span>
                                    @php
                                        $lateMinutes = 0;
                                        $isLate = false;
                                        if ($selectedAbsenDetail->jam_masuk && $activeShift) {
                                            $jamMasukTime = \Carbon\Carbon::parse($selectedAbsenDetail->jam_masuk);
                                            $shiftMasukTime = \Carbon\Carbon::parse($activeShift->jam_masuk);
                                            if ($jamMasukTime->gt($shiftMasukTime)) {
                                                $lateMinutes = $jamMasukTime->diffInMinutes($shiftMasukTime);
                                                $isLate = $lateMinutes > 0;
                                            }
                                        }
                                    @endphp
                                    @if ($isLate)
                                        <span class="inline-flex px-2 py-0.5 rounded bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 text-[10px] font-extrabold uppercase tracking-wide">
                                            Terlambat {{ $lateMinutes }} Menit
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 text-[10px] font-extrabold uppercase tracking-wide">
                                            Tepat Waktu
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Clock Out Details --}}
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-lg bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                                        <iconify-icon icon="lucide:log-out" class="text-sm"></iconify-icon>
                                    </div>
                                    <span class="font-medium text-neutral-700 dark:text-neutral-300">Jam Pulang</span>
                                </div>
                                <div class="text-right">
                                    <span class="block font-bold text-neutral-900 dark:text-neutral-100">
                                        {{ $selectedAbsenDetail->jam_keluar ? \Carbon\Carbon::parse($selectedAbsenDetail->jam_keluar)->format('H:i:s') : 'Belum Absen Pulang' }}
                                    </span>
                                    @if ($selectedAbsenDetail->jam_keluar)
                                        @php
                                            $isEarly = false;
                                            $earlyMinutes = 0;
                                            if ($activeShift) {
                                                $jamKeluarTime = \Carbon\Carbon::parse($selectedAbsenDetail->jam_keluar);
                                                $shiftKeluarTime = \Carbon\Carbon::parse($activeShift->jam_keluar);
                                                if ($jamKeluarTime->lt($shiftKeluarTime)) {
                                                    $isEarly = true;
                                                    $earlyMinutes = $shiftKeluarTime->diffInMinutes($jamKeluarTime);
                                                }
                                            }
                                        @endphp
                                        @if ($isEarly)
                                            <span class="inline-flex px-2 py-0.5 rounded bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 text-[10px] font-extrabold uppercase tracking-wide">
                                                Pulang Cepat ({{ $earlyMinutes }} Mnt)
                                            </span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 text-[10px] font-extrabold uppercase tracking-wide">
                                                Sesuai Jadwal
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-neutral-100 dark:border-neutral-800/60 my-4"></div>

                    {{-- Financial Status / Denda --}}
                    <div class="flex items-center justify-between p-3 bg-red-50/50 dark:bg-rose-950/10 border border-red-100/50 dark:border-rose-900/20 rounded-2xl">
                        <div class="flex items-center gap-2 text-rose-800 dark:text-rose-300 font-semibold text-xs">
                            <iconify-icon icon="lucide:dollar-sign" class="text-sm"></iconify-icon>
                            Status Keuangan (Denda)
                        </div>
                        <div class="text-right">
                            @php
                                $denda = 0;
                                if ($isLate && $activeShift) {
                                    $denda = $activeShift->denda_telat ?? 20000;
                                }
                            @endphp
                            @if ($denda > 0)
                                <span class="font-bold text-rose-600 dark:text-rose-400">
                                    Rp {{ number_format($denda, 0, ',', '.') }}
                                </span>
                            @else
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                    Nihil
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 border-t border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/50 flex justify-end">
                    <button type="button" wire:click="closeDetailModal"
                        class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-700 text-xs font-bold transition shadow-sm">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
