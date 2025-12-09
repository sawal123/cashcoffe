<div>
    {{-- If your happiness depends on money, you will never be happy with yourself. --}}
    <x-toast />
    <div class="bg-white dark:bg-neutral-800 rounded-xl p-4 mb-4">
        <h5 class="text-lg font-semibold mb-1">{{ $user->name }}</h5>
        <p class="text-sm text-neutral-500 mb-4">{{ $user->email }}</p>

        {{-- SUMMARY --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">

            <div class="border dark:bg-neutral-600 rounded p-3 text-center">
                <p class="text-xs ">Complete</p>
                <p class="text-xl font-semibold ">{{ $totalHadir }}</p>
            </div>

            <div class="border dark:bg-neutral-600 rounded p-3 text-center">
                <p class="text-xs">Terlambat</p>
                <p class="text-xl font-semibold">{{ $totalTerlambat }}</p>
            </div>

            <div class="border dark:bg-neutral-600 rounded p-3 text-center">
                <p class="text-xs ">Izin</p>
                <p class="text-xl font-semibold ">{{ $totalIzin }}</p>
            </div>

            <div class="border dark:bg-neutral-600 rounded p-3 text-center">
                <p class="text-xs ">Sakit</p>
                <p class="text-xl font-semibold ">{{ $totalSakit }}</p>
            </div>

            <div class="border dark:bg-neutral-600 rounded p-3 text-center">
                <p class="text-xs ">Cuti</p>
                <p class="text-xl font-semibold ">{{ $totalCuti }}</p>
            </div>

            <div class="border dark:bg-neutral-600 rounded p-3 text-center">
                <p class="text-xs ">Alpha</p>
                <p class="text-xl font-semibold ">{{ $totalAlpha }}</p>
            </div>

        </div>

    </div>


    <div class="flex gap-2 mb-3">
        <div x-data="{ open: false }" class="relative w-44">
            <button @click="open = !open"
                class="w-full flex justify-between items-center px-3 py-2 bg-white dark:bg-gray-800 border rounded-lg text-sm">
                <span>
                    {{ \Carbon\Carbon::create()->month($month)->format('F') }}
                </span>
                <iconify-icon icon="mdi:chevron-down"></iconify-icon>
            </button>

            <div x-show="open" @click.outside="open = false" x-transition
                class="absolute mt-1 w-full bg-white dark:bg-gray-700 rounded-lg shadow-xl z-20">

                <ul class="py-1 text-sm text-gray-700 dark:text-gray-200 max-h-60 overflow-y-auto">
                    @foreach (range(1, 12) as $m)
                    <li>
                        <a href="javascript:void(0)" wire:click="$set('month', {{ $m }})" @click="open = false" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600
                        {{ $month == $m ? 'bg-gray-100 dark:bg-gray-600 font-semibold' : '' }}">
                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div x-data="{ open: false }" class="relative w-32">
            <button @click="open = !open"
                class="w-full flex justify-between items-center px-3 py-2 bg-white dark:bg-gray-800 border rounded-lg text-sm">
                <span>{{ $year }}</span>
                <iconify-icon icon="mdi:chevron-down"></iconify-icon>
            </button>

            <div x-show="open" @click.outside="open = false" x-transition
                class="absolute mt-1 w-full bg-white dark:bg-gray-700 rounded-lg shadow-xl z-20">

                <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                    @foreach (range(now()->year - 2, now()->year) as $y)
                    <li>
                        <a href="javascript:void(0)" wire:click="$set('year', {{ $y }})" @click="open = false" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600
                        {{ $year == $y ? 'bg-gray-100 dark:bg-gray-600 font-semibold' : '' }}">
                            {{ $y }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

    </div>

    <div class="table-responsive">
        <table class="table basic-border-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tanggal</th>
                    <th>Masuk</th>
                    <th>Keluar</th>
                    <th>Status</th>
                    <th>Detail</th>
                </tr>
            </thead>


            <tbody>
                @forelse ($calendar as $i => $row)
                @php
                $item = $row['absen'];
                $tanggal = \Carbon\Carbon::parse($row['tanggal']);

                $terlambat = false;
                if ($item && $item->jam_masuk && $shift) {
                $terlambat = \Carbon\Carbon::parse($item->jam_masuk)
                ->gt(\Carbon\Carbon::parse($shift->jam_masuk));
                }
                @endphp

                <tr class="{{ $item ? '' : 'opacity-70' }}">
                    {{-- NO --}}
                    <td>{{ $i + 1 }}</td>

                    {{-- TANGGAL (WAJIB ADA) --}}
                    <td class="font-medium">
                        {{ $tanggal->format('d M Y') }}
                    </td>

                    {{-- JAM MASUK --}}
                    <td>
                        @if ($item?->jam_masuk)
                        <span class="{{ $terlambat ? 'text-danger-600 font-semibold' : '' }}">
                            {{ $item->jam_masuk }}
                        </span>
                        @else
                        <span class="text-neutral-400">--</span>
                        @endif
                    </td>

                    {{-- JAM KELUAR --}}
                    <td>
                        {{ $item?->jam_keluar ?? '--' }}
                    </td>

                    {{-- STATUS --}}
                    <td>
                        @php
                        $isToday = $tanggal->isToday();
                        $isPast = $tanggal->isPast();
                        @endphp

                        @if ($item)
                        @php
                        $badge = match($item->status) {
                        'hadir' => 'bg-success-600',
                        'terlambat' => 'bg-warning-600',
                        'izin', 'sakit', 'cuti' => 'bg-blue-600',
                        'alpha' => 'bg-danger-600',
                        'complete' => 'bg-neutral-600',
                        default => 'bg-gray-400'
                        };
                        @endphp
                        <span class="px-3 py-1 rounded-full text-xs text-white {{ $badge }}">
                            {{ ucfirst(str_replace('_',' ', $item->status)) }}
                        </span>
                        @else
                        @if ($isPast)
                        <span class="px-3 py-1 rounded-full text-xs bg-danger-600 text-white">
                            Alpha
                        </span>
                        @elseif ($isToday)
                        <span class="px-3 py-1 rounded-full text-xs bg-warning-400 text-white">
                            Belum Absen
                        </span>
                        @else
                        <span class="text-neutral-400">—</span>
                        @endif
                        @endif
                    </td>


                    <td>
                        @if ($item)
                        <button @click="$dispatch('open-modal', {
        name: 'detail-absense',
        id: {{ $item->id }}
    })" class="px-3 py-1 text-sm bg-primary-100 text-primary-600 rounded hover:scale-105 transition">
                            Detail
                        </button>

                        <button @click="$dispatch('open-modal', {
        name: 'update-status',
        id: {{ $item->id }}
    })" class="px-3 py-1 text-sm bg-warning-600 rounded hover:scale-105 transition text-white">
                            Status
                        </button>
                        @else
                        <span class="text-xs text-neutral-400 italic">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4">Tidak ada data</td>
                </tr>
                @endforelse
            </tbody>

        </table>

        {{-- {{ $absensis->links(data: ['scroll' => false]) }} --}}
    </div>

    <x-mdal name="detail-absense">
        <div class="px-5 py-4 max-h-[80vh] overflow-y-auto" x-data @open-modal.window="
            if ($event.detail.name === 'detail-absense') {
                $wire.loadAbsenseDetail($event.detail.id);
            }
        ">

            <h3 class="mb-3 text-base font-semibold text-neutral-800 dark:text-neutral-100 text-center">
                Detail Absensi
            </h3>

            {{-- CLOCK IN --}}
            <div class="border rounded-md p-3 mb-3 text-sm">
                <p class="font-medium text-success-600 mb-1">Clock In</p>

                <p class="text-xs text-neutral-600">
                    Jam Masuk: <span class="font-medium">{{ $selected?->jam_masuk ?? '-' }}</span>
                </p>

                @php
                $lat = $lng = null;
                if ($selected && $selected->lokasi && str_contains($selected->lokasi, ',')) {
                [$lat, $lng] = explode(',', $selected->lokasi);
                }
                @endphp

                @if($lat && $lng)
                <div class="mt-2 rounded border overflow-hidden">
                    <iframe width="100%" height="180" loading="lazy"
                        src="https://www.openstreetmap.org/export/embed.html?bbox={{ $lng-0.005 }},{{ $lat-0.005 }},{{ $lng+0.005 }},{{ $lat+0.005 }}&layer=mapnik&marker={{ $lat }},{{ $lng }}">
                    </iframe>
                </div>

                <p class="text-[11px] text-neutral-500 mt-1">
                    {{ $alamatMasuk ?? 'Alamat tidak ditemukan' }}
                </p>

                @else
                <p class="text-xs text-neutral-400 mt-1">Lokasi tidak tersedia</p>
                @endif

                @if($selected?->foto)
                <img src="{{ Storage::url($selected->foto) }}" class="rounded mt-2 w-full max-h-48 object-cover">
                @endif
            </div>

            {{-- CLOCK OUT --}}
            <div class="border rounded-md p-3 text-sm">
                <p class="font-medium text-danger-600 mb-1">Clock Out</p>

                <p class="text-xs text-neutral-600">
                    Jam Keluar: <span class="font-medium">{{ $selected?->jam_keluar ?? '-' }}</span>
                </p>

                @php
                $latOut = $lngOut = null;
                if ($selected && $selected->lokasi_keluar && str_contains($selected->lokasi_keluar, ',')) {
                [$latOut, $lngOut] = explode(',', $selected->lokasi_keluar);
                }
                @endphp

                @if($latOut && $lngOut)
                <div class="mt-2 rounded border overflow-hidden">
                    <iframe width="100%" height="180" loading="lazy"
                        src="https://www.openstreetmap.org/export/embed.html?bbox={{ $lngOut-0.005 }},{{ $latOut-0.005 }},{{ $lngOut+0.005 }},{{ $latOut+0.005 }}&layer=mapnik&marker={{ $latOut }},{{ $lngOut }}">
                    </iframe>
                </div>

                <p class="text-[11px] text-neutral-500 mt-1">
                    {{ $alamatKeluar ?? 'Alamat tidak ditemukan' }}
                </p>
                @else
                <p class="text-xs text-neutral-400 mt-1">Belum Clock Out</p>
                @endif

                @if($selected?->foto_keluar)
                <img src="{{ Storage::url($selected->foto_keluar) }}" class="rounded mt-2 w-full max-h-48 object-cover">
                @endif
            </div>

            {{-- ACTION --}}
            <div class="flex justify-center border-t pt-3 mt-4 border-neutral-200 dark:border-neutral-700">
                <button type="button" x-on:click="modalIsOpen = false"
                    class="px-4 py-1.5 rounded-md border bg-dark text-xs">
                    Tutup
                </button>
            </div>

        </div>
    </x-mdal>

    <x-mdal name="update-status">
        <div class="px-5 py-4 max-h-[80vh] overflow-y-auto" x-data @open-modal.window="
            if ($event.detail.name === 'update-status') {
                $wire.loadStatus($event.detail.id)
            }
        ">
            <h4 class="text-sm font-semibold mb-3 text-center">
                Update Status Absensi
            </h4>

            {{-- STATUS --}}
            <div class="mb-3">
                <label class="text-xs text-neutral-500 mb-1 block">
                    Status Kehadiran
                </label>
                <select wire:model="status" class="w-full rounded-lg border border-slate-300 dark:border-slate-700
               bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200
               px-3 py-2 text-sm focus:outline-none focus:ring-2
               focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer">
                    <option value="">-- Pilih Status --</option>
                    <option value="hadir">Hadir</option>
                    <option value="terlambat">Terlambat</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="cuti">Cuti</option>
                    <option value="alpha">Alpha</option>
                    <option value="wfh">WFH</option>
                    <option value="dinas_luar">Dinas Luar</option>
                    <option value="complete">Complete</option>
                </select>
            </div>

            {{-- KETERANGAN --}}
            <div class="mb-4">
                <label class="text-xs text-neutral-500 mb-1 block">
                    Keterangan (opsional)
                </label>
                <textarea wire:model="keterangan" rows="3" class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                       bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100
                       px-3 py-2 text-sm focus:outline-none focus:ring-2
                       focus:ring-blue-500/40 focus:border-blue-500 transition"></textarea>
            </div>

            {{-- ACTION --}}
            <div class="flex justify-end gap-2 border-t pt-3">
                <button @click="modalIsOpen=false" class="px-4 py-2 text-sm rounded border">
                    Batal
                </button>

                <button wire:click="updateStatus" class="px-4 py-2 text-sm rounded bg-primary-600 text-white">
                    Simpan
                </button>
            </div>
        </div>
    </x-mdal>






</div>
