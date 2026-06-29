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
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Pengeluaran' }}</h6>
        <x-breadcrumb :title="$title ?? 'Pengeluaran'" />
    </div>

    <x-toast />

    @php
        $catColors = [
            'Bahan Baku' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'Gaji Karyawan' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'Listrik & Air' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'Kebersihan' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
            'Peralatan' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
            'Konsumsi' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
            'Operasional' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
            'Transport' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'Lainnya' => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400',
        ];

        $periodLabel = 'Semua tanggal';
        if ($dateFrom && $dateTo) {
            $periodLabel = \Carbon\Carbon::parse($dateFrom)->translatedFormat('d M Y') . ' - ' . \Carbon\Carbon::parse($dateTo)->translatedFormat('d M Y');
        } elseif ($dateFrom) {
            $periodLabel = 'Mulai ' . \Carbon\Carbon::parse($dateFrom)->translatedFormat('d M Y');
        } elseif ($dateTo) {
            $periodLabel = 'Sampai ' . \Carbon\Carbon::parse($dateTo)->translatedFormat('d M Y');
        }
    @endphp

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-8">
        {{-- Total Filtered --}}
        <div
            class="md:col-span-4 bg-white dark:bg-neutral-800 rounded-3xl p-8 shadow-sm border border-neutral-100 dark:border-neutral-700 relative overflow-hidden">
            <h4 class="text-xs font-bold text-neutral-400 uppercase tracking-widest mb-1">
                Total Periode Terpilih
            </h4>
            <p class="text-3xl font-black text-blue-600 dark:text-blue-500 mb-4">
                Rp {{ number_format($this->totalFiltered, 0, ',', '.') }}
            </p>
            <div class="flex items-center gap-2 text-[11px] text-neutral-400">
                <iconify-icon icon="lucide:calendar-range" class="text-blue-400"></iconify-icon>
                <span>{{ $periodLabel }}</span>
            </div>
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 dark:bg-blue-900/10 rounded-full opacity-50">
            </div>
        </div>

        {{-- Total Seluruhnya (Dark) --}}
        <div
            class="md:col-span-8 bg-neutral-900 rounded-3xl p-8 shadow-xl shadow-neutral-900/20 flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h4 class="text-xs font-bold text-neutral-500 uppercase tracking-widest mb-1">
                    Total Seluruhnya
                </h4>
                <p class="text-4xl font-black text-white">
                    Rp {{ number_format($this->totalAllTime, 0, ',', '.') }}
                </p>
            </div>
            <div
                class="bg-neutral-800/80 backdrop-blur-sm rounded-2xl p-4 border border-neutral-700 flex items-center gap-4 w-full md:w-64">
                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center">
                    <iconify-icon icon="lucide:trending-up" class="text-white text-xl"></iconify-icon>
                </div>
                <div>
                    <span class="text-[10px] text-neutral-500 font-bold uppercase block -mb-1">Bulan Ini</span>
                    <span class="text-sm font-black text-white">+{{ number_format($this->growthPercentage, 1) }}% <span
                            class="text-[10px] font-medium text-neutral-500 ml-1">dari bulan lalu</span></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Bar --}}
    <div class="bg-indigo-50/50 dark:bg-neutral-800/50 rounded-3xl p-4 mb-8 flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[260px]">
            <x-ui.input prefix="" placeholder="Cari pengeluaran (kategori, deskripsi, dll)..."
                wire:model.live="search" />
        </div>

        <div class="min-w-[280px] sm:min-w-[340px] flex-1 sm:flex-none">
            <div class="relative ui-date-range-field" wire:ignore>
                <input id="pengeluaranDateRange" type="text" readonly value="{{ $dateRange }}"
                    placeholder="Pilih rentang tanggal"
                    class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-2xl py-3 pl-[60px] pr-4 text-sm font-semibold text-neutral-700 dark:text-neutral-300 placeholder:text-neutral-400 focus:ring-2 focus:ring-blue-500 cursor-pointer">
                <iconify-icon icon="lucide:calendar-range"
                    class="absolute left-4 top-1/2 -translate-y-1/2 text-lg text-blue-500 z-10 pointer-events-none"></iconify-icon>
            </div>
        </div>

        <button type="button" wire:click="resetDateRange"
            class="h-12 px-4 flex items-center gap-2 bg-white dark:bg-neutral-900 rounded-2xl shadow-sm text-xs font-bold text-neutral-500 hover:text-blue-600 transition-all">
            <iconify-icon icon="lucide:rotate-ccw" class="text-base"></iconify-icon>
            Reset Tanggal
        </button>

        <x-ui.button-link href="/pengeluaran/create" icon="mingcute:add-circle-line">
            Tambah Pengeluaran
        </x-ui.button-link>
    </div>

    {{-- Premium Table --}}
    <x-ui.table :headers="['#', 'Tanggal & Cabang', 'Kategori & Deskripsi', ['name' => 'Qty', 'align' => 'center'], ['name' => 'Total', 'align' => 'right'], 'Metode', ['name' => 'Bukti', 'align' => 'center'], ['name' => 'Aksi', 'align' => 'center']]">
        @forelse ($pengeluarans as $item)
            <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/30 transition-colors group">
                <td data-label="#" class="px-6 py-6 text-xs font-bold text-neutral-300 group-hover:text-neutral-500">
                    {{ ($pengeluarans->currentPage() - 1) * $pengeluarans->perPage() + $loop->iteration }}
                </td>
                <td data-label="Tanggal & Cabang" class="px-6 py-6">
                    <span
                        class="block text-sm font-bold text-neutral-800 dark:text-neutral-200">{{ \Carbon\Carbon::parse($item->tanggal_pengeluaran)->translatedFormat('d M Y') }}</span>
                    <span class="block text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase tracking-widest mt-1">
                        {{ $item->branch ? $item->branch->nama_cabang : 'Pusat' }}
                    </span>
                </td>
                <td data-label="Keperluan" class="px-6 py-6">
                    <div class="mb-2">
                        <span
                            class="inline-flex px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest {{ $catColors[$item->kategori] ?? $catColors['Lainnya'] }}">
                            {{ $item->kategori ?? 'UMUM' }}
                        </span>
                    </div>
                    <p class="text-xs font-bold text-neutral-700 dark:text-neutral-300 leading-relaxed">
                        {{ $item->title }}
                    </p>
                    @if($item->catatan)
                        <p class="text-[10px] text-neutral-400 mt-1 italic line-clamp-1">{{ $item->catatan }}</p>
                    @endif
                </td>
                <td data-label="Qty" class="px-6 py-6 text-center">
                    <div class="inline-flex flex-col items-center">
                        <span class="text-xs font-black text-neutral-800 dark:text-neutral-200">{{ $item->jumlah ?? '1' }}</span>
                        <span class="text-[9px] font-bold text-neutral-400 uppercase tracking-tighter">
                            {{ $item->satuanBahan ? $item->satuanBahan->nama_satuan : ($item->satuan ?? '-') }}
                        </span>
                    </div>
                </td>
                <td data-label="Total" class="px-6 py-6 text-right">
                    <span class="block text-[10px] text-neutral-300 font-bold -mb-1">Rp</span>
                    <span
                        class="text-sm font-black text-neutral-800 dark:text-neutral-100">{{ number_format($item->total, 0, ',', '.') }}</span>
                </td>
                <td data-label="Metode" class="px-6 py-6">
                    @php
                        $metodeIcon = match ($item->metode_pembayaran) {
                            'Cash' => ['icon' => 'lucide:banknote', 'color' => 'text-emerald-500'],
                            'Transfer Bank' => ['icon' => 'lucide:laptop', 'color' => 'text-blue-500'],
                            'E-Wallet' => ['icon' => 'lucide:wallet', 'color' => 'text-purple-500'],
                            default => ['icon' => 'lucide:credit-card', 'color' => 'text-neutral-400']
                        };
                    @endphp
                    <div class="flex items-center gap-2">
                        <iconify-icon icon="{{ $metodeIcon['icon'] }}"
                            class="{{ $metodeIcon['color'] }} text-sm"></iconify-icon>
                        <span
                            class="text-[11px] font-bold text-neutral-500">{{ $item->metode_pembayaran ?? 'Debit Card' }}</span>
                    </div>
                </td>
                <td data-label="Bukti" class="px-6 py-6 text-center">
                    @if ($item->bukti)
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($item->bukti) }}" target="_blank"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white transition-all">
                            <iconify-icon icon="lucide:image" class="text-sm"></iconify-icon>
                        </a>
                    @else
                        <iconify-icon icon="lucide:image"
                            class="text-neutral-200 dark:text-neutral-700 opacity-50"></iconify-icon>
                    @endif
                </td>
                <td data-label="Aksi" class="px-6 py-6">
                    <div class="flex items-center justify-center gap-2">
                        <x-ui.action-edit :href="route('pengeluaran.edit', $item->id)" wire:navigate />
                        <x-ui.action-delete
                            @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id)) }} })" />
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center py-20">
                    <div class="flex flex-col items-center">
                        <iconify-icon icon="lucide:folder-search" class="text-5xl text-neutral-200 mb-2"></iconify-icon>
                        <p class="text-sm font-bold text-neutral-400 tracking-wide">Tidak ada data ditemukan</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    @if($pengeluarans->hasPages())
        <div class="px-6 py-6 border-t border-neutral-100 dark:border-neutral-700 bg-neutral-50/30 dark:bg-neutral-900/10">
            {{ $pengeluarans->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
        </div>
    @endif


    {{-- Modal Konfirmasi Hapus --}}
    <x-mdl>
        <div class="px-6 py-2 text-center">
            <h3 class="font-black text-xl text-neutral-800 dark:text-neutral-100">Hapus Data?</h3>
            <p class="text-sm text-neutral-500 mt-2">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
        </div>
        <div class="flex justify-center gap-3 p-6 pt-2">
            <button x-on:click="modalIsOpen = false"
                class="flex-1 px-6 py-3 rounded-2xl border border-neutral-200 bg-white text-sm font-bold text-neutral-600 hover:bg-neutral-50 transition-all dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-300">
                Cancel
            </button>
            <button x-on:click="$wire.deletePengeluaran(selectedId); modalIsOpen = false"
                class="flex-1 px-6 py-3 rounded-2xl bg-rose-600 text-sm font-bold text-white hover:bg-rose-700 shadow-lg shadow-rose-600/20 transition-all active:scale-95">
                Delete
            </button>
        </div>
    </x-mdl>

    <script src="{{ asset('assets/js/flatpickr.js') }}" data-navigate-once></script>
    <script>
        function initPengeluaranDateRangePicker() {
            const input = document.getElementById('pengeluaranDateRange');

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

        document.addEventListener('livewire:navigated', initPengeluaranDateRangePicker);
        document.addEventListener('livewire:initialized', initPengeluaranDateRangePicker);
        document.addEventListener('pengeluaran-date-range-reset', () => {
            const input = document.getElementById('pengeluaranDateRange');
            if (input && input._flatpickr) {
                input._flatpickr.clear();
            }
        });
        setTimeout(initPengeluaranDateRangePicker, 80);
    </script>
</div>
