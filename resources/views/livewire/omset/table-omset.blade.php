<div>
    <x-toast />
    <div class="space-y-4">
        {{-- CARD TOTAL BULANAN --}}
        <div class="grid md:grid-cols-2 gap-4">
            <div
                class="card border border-neutral-200 dark:border-neutral-700 p-4 bg-white dark:bg-neutral-800 rounded-lg shadow-sm">
                <h4 class="font-semibold text-neutral-600 dark:text-neutral-300 mb-1">
                    Total Omset Bulan Ini
                </h4>
                <p class="text-2xl font-bold text-neutral-800 dark:text-white">
                    Rp {{ number_format($totalOmset, 0, ',', '.') }}
                </p>
            </div>
            <div
                class="card border border-neutral-200 dark:border-neutral-700 p-4 bg-white dark:bg-neutral-800 rounded-lg shadow-sm">
                <h4 class="font-semibold text-neutral-600 dark:text-neutral-300 mb-1">
                    Total Profit Bulan Ini
                </h4>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                    Rp {{ number_format($totalProfit, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- FILTER BULAN & TAHUN --}}
        <div class="flex flex-wrap gap-3 items-end mt-2">
            <div>
                <label class="text-sm font-medium">Bulan</label>
                <select wire:model.live="bulan"
                    class="border border-neutral-300 dark:border-neutral-600 rounded-md px-3 py-2 text-sm dark:bg-neutral-800 dark:text-neutral-100">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}">
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Tahun</label>
                <select wire:model.live="tahun"
                    class="border border-neutral-300 dark:border-neutral-600 rounded-md px-3 py-2 text-sm dark:bg-neutral-800 dark:text-neutral-100">
                    @foreach (range(date('Y') - 5, date('Y')) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- TABLE OMSET HARIAN --}}
        <div class="table-responsive mt-3">
            <table class="table basic-border-table mb-2">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>Jumlah Pesanan</th>
                        <th>Jumlah Menu</th>
                        <th>Jumlah Omset</th>
                        <th>Jumlah Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dataOmset as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}</td>
                            <td>{{ number_format($item->jumlah_pesanan, 0, ',', '.') }}</td>
                            <td>{{ number_format($item->jumlah_menu, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($item->total_omset, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($item->total_profit, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-neutral-500">
                                Tidak ada data omset untuk bulan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>





</div>
