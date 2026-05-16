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
            <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/30 transition-colors group">
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
                    <a href="{{ route('absense.show', $user->id) }}" wire:navigate
                        class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white transition-all">
                        <iconify-icon icon="lucide:eye" class="text-sm"></iconify-icon>
                    </a>
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
</div>
