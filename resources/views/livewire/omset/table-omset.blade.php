<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Omset' }}</h6>
        <x-breadcrumb :title="$title ?? 'Omset'" />
    </div>
    <x-toast />
    <div class="space-y-6">
        {{-- CARD TOTAL BULANAN --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-6 rounded-[2rem] shadow-sm">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <iconify-icon icon="mingcute:wallet-line" class="text-xl"></iconify-icon>
                    </div>
                    <h4 class="text-xs font-black text-neutral-400 uppercase tracking-widest">
                        Omset Bulan Ini
                    </h4>
                </div>
                <p class="text-2xl font-bold text-neutral-900 dark:text-white">
                    Rp {{ number_format($totalOmset, 0, ',', '.') }}
                </p>
            </div>

            <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-6 rounded-[2rem] shadow-sm">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400">
                        <iconify-icon icon="mingcute:high-light" class="text-xl"></iconify-icon>
                    </div>
                    <h4 class="text-xs font-black text-neutral-400 uppercase tracking-widest">
                        Profit Bulan Ini
                    </h4>
                </div>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    Rp {{ number_format($totalProfit, 0, ',', '.') }}
                </p>
            </div>

            <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-6 rounded-[2rem] shadow-sm">
                <div class="flex items-center gap-4 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <iconify-icon icon="mingcute:gift-line" class="text-xl"></iconify-icon>
                    </div>
                    <h4 class="text-xs font-black text-neutral-400 uppercase tracking-widest">
                        Komplemen Bulan Ini
                    </h4>
                </div>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                    Rp {{ number_format($totalKomplemen, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- FILTER BULAN & TAHUN --}}
        <div class="flex flex-wrap gap-4 items-center bg-white dark:bg-neutral-800/50 p-6 rounded-[2rem] border border-neutral-200 dark:border-neutral-700">
            <div class="w-full md:w-[200px]">
                <x-ui.select wire:model.live="bulan" label="Pilih Bulan">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="w-full md:w-[150px]">
                <x-ui.select wire:model.live="tahun" label="Pilih Tahun">
                    @foreach (range(date('Y') - 5, date('Y')) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </x-ui.select>
            </div>
        </div>

        {{-- TABLE OMSET HARIAN --}}
        <x-ui.table :headers="[
            ['name' => '#', 'align' => 'center'],
            'Tanggal',
            ['name' => 'Pesanan', 'align' => 'center'],
            ['name' => 'Menu', 'align' => 'center'],
            ['name' => 'Komplemen', 'align' => 'center'],
            'Omset Total',
            'Estimated Profit'
        ]">
            @forelse ($dataOmset as $index => $item)
                <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition">
                    <td data-label="#" class="px-6 py-4 text-center text-sm text-neutral-500">{{ $index + 1 }}</td>
                    <td data-label="Tanggal" class="px-6 py-4">
                        <span class="font-semibold text-neutral-800 dark:text-neutral-200">
                            {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}
                        </span>
                    </td>
                    <td data-label="Pesanan" class="px-6 py-4 text-center text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        {{ number_format($item->jumlah_pesanan, 0, ',', '.') }}
                    </td>
                    <td data-label="Menu" class="px-6 py-4 text-center text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        {{ number_format($item->jumlah_menu, 0, ',', '.') }}
                    </td>
                    <td data-label="Komplemen" class="px-6 py-4 text-center">
                        <span class="px-2.5 py-1 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg text-xs font-bold border border-amber-100 dark:border-amber-700">
                            {{ number_format($item->total_komplemen, 0, ',', '.') }}
                        </span>
                    </td>
                    <td data-label="Omset Total" class="px-6 py-4">
                        <span class="font-bold text-neutral-900 dark:text-white">
                            Rp {{ number_format($item->total_omset, 0, ',', '.') }}
                        </span>
                    </td>
                    <td data-label="Estimated Profit" class="px-6 py-4">
                        <span class="font-bold text-green-600 dark:text-green-400">
                            Rp {{ number_format($item->total_profit, 0, ',', '.') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-12 text-neutral-500">
                        <div class="flex flex-col items-center justify-center gap-3">
                            <iconify-icon icon="mingcute:ghost-line" class="text-4xl"></iconify-icon>
                            <span class="text-sm">Tidak ada data omset untuk bulan ini.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>
</div>
