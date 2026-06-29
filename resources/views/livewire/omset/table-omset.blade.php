<div>
    <style>
        .ui-date-range-field .flatpickr-input[readonly],
        .ui-date-range-field .flatpickr-input[readonly="readonly"],
        .ui-date-range-field .ui-date-range-alt {
            width: 100%;
            background: rgb(250 250 250);
            color: rgb(64 64 64);
            border: 1px solid rgb(229 229 229);
            border-radius: 1rem;
            padding: 0.75rem 1rem 0.75rem 3.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1.25rem;
            box-shadow: none;
            cursor: pointer;
        }

        .dark .ui-date-range-field .flatpickr-input[readonly],
        .dark .ui-date-range-field .flatpickr-input[readonly="readonly"],
        .dark .ui-date-range-field .ui-date-range-alt {
            background: rgb(23 23 23);
            color: rgb(212 212 212);
            border-color: rgb(64 64 64);
        }

        .ui-date-range-field .ui-date-range-alt:focus,
        .ui-date-range-field .flatpickr-input[readonly]:focus,
        .ui-date-range-field .flatpickr-input[readonly="readonly"]:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgb(59 130 246 / 0.25);
            border-color: rgb(59 130 246);
        }
    </style>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Omset' }}</h6>
        <x-breadcrumb :title="$title ?? 'Omset'" />
    </div>
    <x-toast />
    @php
        $periodLabel = 'Semua tanggal';
        if ($dateFrom && $dateTo) {
            $periodLabel = \Carbon\Carbon::parse($dateFrom)->translatedFormat('d M Y') . ' - ' . \Carbon\Carbon::parse($dateTo)->translatedFormat('d M Y');
        } elseif ($dateFrom) {
            $periodLabel = 'Mulai ' . \Carbon\Carbon::parse($dateFrom)->translatedFormat('d M Y');
        } elseif ($dateTo) {
            $periodLabel = 'Sampai ' . \Carbon\Carbon::parse($dateTo)->translatedFormat('d M Y');
        }
    @endphp
    <div class="space-y-6">
        {{-- CARD TOTAL PERIODE --}}
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            {{-- Omset Card --}}
            <div
                class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-4 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <div
                        class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <i class="ri-wallet-3-line text-lg leading-none"></i>
                    </div>
                    <h4 class="text-[10px] font-black text-neutral-400 uppercase tracking-widest">
                        Omset Periode
                    </h4>
                </div>
                <p class="text-lg font-bold text-neutral-900 dark:text-white leading-tight">
                    Rp {{ number_format($totalOmset, 0, ',', '.') }}
                </p>
            </div>

            {{-- Profit Estimasi Card --}}
            <div
                class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-4 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <div
                        class="w-8 h-8 rounded-lg bg-green-50 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400">
                        <i class="ri-line-chart-line text-lg leading-none"></i>
                    </div>
                    <h4 class="text-[10px] font-black text-neutral-400 uppercase tracking-widest">
                        Profit Estimasi
                    </h4>
                </div>
                <p class="text-lg font-bold text-green-600 dark:text-green-400 leading-tight">
                    Rp {{ number_format($totalProfit, 0, ',', '.') }}
                </p>
            </div>

            {{-- Komplemen Card --}}
            <div
                class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-4 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <div
                        class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <i class="ri-gift-line text-lg leading-none"></i>
                    </div>
                    <h4 class="text-[10px] font-black text-neutral-400 uppercase tracking-widest">
                        Komplemen
                    </h4>
                </div>
                <p class="text-lg font-bold text-amber-600 dark:text-amber-400 leading-tight">
                    Rp {{ number_format($totalKomplemen, 0, ',', '.') }}
                </p>
            </div>

            {{-- Pengeluaran Card --}}
            <div
                class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-4 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <div
                        class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400">
                        <i class="ri-bank-card-line text-lg leading-none"></i>
                    </div>
                    <h4 class="text-[10px] font-black text-neutral-400 uppercase tracking-widest">
                        Pengeluaran
                    </h4>
                </div>
                <p class="text-lg font-bold text-red-600 dark:text-red-400 leading-tight">
                    Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}
                </p>
            </div>

            {{-- Profit Bersih Card --}}
            <div
                class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-4 rounded-2xl shadow-sm">
                <div class="flex items-center gap-3 mb-2">
                    <div
                        class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                        <i class="ri-money-dollar-circle-line text-lg leading-none"></i>
                    </div>
                    <h4 class="text-[10px] font-black text-neutral-400 uppercase tracking-widest">
                        Profit Bersih
                    </h4>
                </div>
                <p class="text-lg font-bold text-purple-600 dark:text-purple-400 leading-tight">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- FILTER TANGGAL --}}
        <div
            class="flex flex-wrap gap-4 items-end bg-white dark:bg-neutral-800/50 p-6 rounded-[2rem] border border-neutral-200 dark:border-neutral-700">
            <div class="w-full md:w-[360px]">
                <label class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-2 block">Rentang Tanggal</label>
                <div class="relative ui-date-range-field" wire:ignore>
                    <input id="omsetDateRange" type="text" readonly value="{{ $dateRange }}"
                        placeholder="Pilih rentang tanggal"
                        class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-2xl py-3 pl-[60px] pr-4 text-sm font-semibold text-neutral-700 dark:text-neutral-300 placeholder:text-neutral-400 focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <iconify-icon icon="lucide:calendar-range"
                        class="absolute left-4 top-1/2 -translate-y-1/2 text-lg text-blue-500 z-10 pointer-events-none"></iconify-icon>
                </div>
            </div>

            <button type="button" wire:click="resetDateRange"
                class="h-12 px-4 flex items-center gap-2 bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-2xl text-xs font-bold text-neutral-500 hover:text-blue-600 transition-all">
                <iconify-icon icon="lucide:rotate-ccw" class="text-base"></iconify-icon>
                Reset Tanggal
            </button>

            <div class="flex items-center gap-2 text-xs text-neutral-400 dark:text-neutral-500 md:ml-auto">
                <iconify-icon icon="lucide:calendar-check" class="text-blue-500"></iconify-icon>
                <span>{{ $periodLabel }}</span>
            </div>
        </div>

        {{-- TABLE OMSET HARIAN --}}
        <x-ui.table :headers="[
            ['name' => '#', 'align' => 'center'],
            'Tanggal',
            ['name' => 'Pesanan', 'align' => 'center'],
            ['name' => 'Menu', 'align' => 'center'],
            ['name' => 'Komplemen', 'align' => 'center'],
            'Pengeluaran',
            'Omset Total',
            'Estimated Profit',
            'Nett Profit',
        ]">
            @forelse ($dataOmset as $index => $item)
                <tr wire:key="omset-row-{{ $item->tanggal }}"
                    class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition">
                    <td data-label="#" class="px-6 py-4 text-center text-sm text-neutral-500">{{ $index + 1 }}</td>
                    <td data-label="Tanggal" class="px-6 py-4">
                        <span class="font-semibold text-neutral-800 dark:text-neutral-200">
                            {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}
                        </span>
                    </td>
                    <td data-label="Pesanan"
                        class="px-6 py-4 text-center text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        {{ number_format($item->jumlah_pesanan, 0, ',', '.') }}
                    </td>
                    <td data-label="Menu"
                        class="px-6 py-4 text-center text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        {{ number_format($item->jumlah_menu, 0, ',', '.') }}
                    </td>
                    <td data-label="Komplemen" class="px-6 py-4 text-center">
                        <span
                            class="px-2.5 py-1 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg text-xs font-bold border border-amber-100 dark:border-amber-700">
                            {{ number_format($item->total_komplemen, 0, ',', '.') }}
                        </span>
                    </td>
                    <td data-label="Pengeluaran" class="px-6 py-4">
                        <span class="font-bold text-red-600 dark:text-red-400">
                            Rp {{ number_format($item->total_pengeluaran, 0, ',', '.') }}
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
                    <td data-label="Nett Profit" class="px-6 py-4">
                        <span
                            class="font-bold {{ $item->net_profit >= 0 ? 'text-purple-600 dark:text-purple-400' : 'text-red-600 dark:text-red-400' }}">
                            Rp {{ number_format($item->net_profit, 0, ',', '.') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center py-12 text-neutral-500">
                        <div class="flex flex-col items-center justify-center gap-3">
                            <i class="ri-inbox-line text-4xl leading-none"></i>
                            <span class="text-sm">Tidak ada data omset untuk periode ini.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>

    <script src="{{ asset('assets/js/flatpickr.js') }}" data-navigate-once></script>
    <script>
        function initOmsetDateRangePicker() {
            const input = document.getElementById('omsetDateRange');

            if (!input || typeof flatpickr === 'undefined') {
                return;
            }

            if (input._flatpickr) {
                input._flatpickr.destroy();
            }

            const dateFrom = @js($dateFrom);
            const dateTo = @js($dateTo);
            const defaultDates = [dateFrom, dateTo].filter(Boolean);

            flatpickr(input, {
                mode: 'range',
                dateFormat: 'Y-m-d',
                altInput: true,
                altInputClass: 'ui-date-range-alt',
                altFormat: 'd M Y',
                defaultDate: defaultDates,
                allowInput: false,
                disableMobile: true,
                onChange(selectedDates, dateStr, instance) {
                    const formatDate = (date) => instance.formatDate(date, 'Y-m-d');
                    const from = selectedDates[0] ? formatDate(selectedDates[0]) : '';
                    const to = selectedDates[1] ? formatDate(selectedDates[1]) : from;

                    @this.call('setDateRange', from, to, dateStr);
                },
            });
        }

        document.addEventListener('livewire:navigated', initOmsetDateRangePicker);
        document.addEventListener('livewire:initialized', initOmsetDateRangePicker);
        document.addEventListener('omset-date-range-reset', () => {
            const input = document.getElementById('omsetDateRange');
            if (input && input._flatpickr) {
                input._flatpickr.clear();
            }
        });
        setTimeout(initOmsetDateRangePicker, 80);
    </script>
</div>
