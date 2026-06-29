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

    <x-toast />

    {{-- Stats Section --}}
    @if (!empty($totalPerMetode))
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            {{-- Omset Card --}}
            <x-ui.card 
                title="Total Omset" 
                :value="'Rp ' . number_format($totalOmset, 0, ',', '.')" 
                icon="lucide:wallet" 
                subtext="+12% dari bulan lalu" 
                color="blue" 
                trending 
                class="md:col-span-2 lg:col-span-1"
            />

            {{-- Method Summary Grid --}}
            <div class="md:col-span-2 lg:col-span-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @php
                    $highestMetode = !empty($totalPerMetode) ? array_search(max($totalPerMetode), $totalPerMetode) : null;
                @endphp
                @foreach ($totalPerMetode as $metode => $total)
                    <x-ui.card 
                        :title="$metode" 
                        :value="'Rp ' . number_format($total, 0, ',', '.')" 
                        :icon="$metode == 'qris' ? 'lucide:qr-code' : ($metode == 'tunai' ? 'lucide:banknote' : ($metode == 'transfer' ? 'lucide:landmark' : 'lucide:credit-card'))" 
                        :subtext="$metode === $highestMetode ? 'Metode paling sering' : null"
                    />
                @endforeach
            </div>
        </div>
    @endif

    {{-- Sales Channel Stats --}}
    @if (!empty($totalPerChannel))
        <div class="mb-8">
            <h3 class="text-[10px] font-black text-neutral-400 dark:text-neutral-500 uppercase tracking-widest mb-3">Total per Sales Channel</h3>
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach ($totalPerChannel as $channel => $total)
                    <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-2xl p-4 flex items-center justify-between shadow-sm hover:shadow transition-all group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-xl flex items-center justify-center">
                                <iconify-icon icon="{{ strtolower($channel) == 'dine in' ? 'lucide:utensils' : (strtolower($channel) == 'take away' ? 'lucide:shopping-bag' : 'lucide:shopping-cart') }}" class="text-base"></iconify-icon>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black text-neutral-400 dark:text-neutral-500 uppercase tracking-wider">{{ $channel }}</span>
                                <span class="text-sm font-black text-neutral-800 dark:text-neutral-200">
                                    Rp {{ number_format($total, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filters & Actions Bar --}}
    <div
        class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-3xl p-4 mb-8 shadow-sm">
        <div class="flex flex-col lg:flex-row items-center justify-between gap-6">
            {{-- Primary Filters --}}
            <div class="flex flex-col sm:flex-row items-center gap-4 w-full lg:w-auto">
                {{-- Search --}}
                <div class="w-full sm:w-64">
                    <x-ui.input wire:model.live="search" placeholder="Cari kode atau nama..." prefix="SRC">
                        <x-slot name="prefix">
                            <iconify-icon icon="lucide:search" class="text-lg"></iconify-icon>
                        </x-slot>
                    </x-ui.input>
                </div>

                {{-- Payment Method --}}
                <div class="w-full sm:w-48">
                    <x-ui.select wire:model.live="filterPembayaran">
                        <option value="">Semua Metode</option>
                        <option value="belum">Belum Bayar</option>
                        @foreach ($pembayaran as $pay)
                            <option value="{{ $pay->id }}">{{ $pay->nama_metode }}</option>
                        @endforeach
                    </x-ui.select>
                </div>

                {{-- Date Range --}}
                <div class="w-full sm:w-[320px] ui-date-range-field" wire:ignore>
                    <div class="relative">
                        <input id="transaksiDateRange" type="text" readonly value="{{ $dateRange }}"
                            placeholder="Pilih rentang tanggal"
                            class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-2xl py-3 pl-[60px] pr-4 text-sm font-semibold text-neutral-700 dark:text-neutral-300 placeholder:text-neutral-400 focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <iconify-icon icon="lucide:calendar-range"
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-lg text-blue-500 z-10 pointer-events-none"></iconify-icon>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 w-full lg:w-auto justify-end">
                <x-ui.button color="blue" wire:click="resetDateRange"
                    class="!px-4 !py-3 !rounded-2xl !bg-white !text-neutral-700 border border-neutral-200 hover:!bg-neutral-50 dark:!bg-neutral-900 dark:!text-neutral-200 dark:border-neutral-700 shadow-none">
                    <iconify-icon icon="lucide:rotate-ccw" class="mr-2"></iconify-icon>
                    Reset
                </x-ui.button>
                <x-ui.button color="blue"
                    class="!px-6 !py-3 !rounded-2xl !bg-white !text-neutral-700 border border-neutral-200 hover:!bg-neutral-50 dark:!bg-neutral-900 dark:!text-neutral-200 dark:border-neutral-700 shadow-none"
                    onclick="printSection('print-area')">
                    <iconify-icon icon="lucide:printer" class="mr-2"></iconify-icon>
                    Print
                </x-ui.button>
                <a href="{{ route('orders.export') }}" wire:navigate>
                    <x-ui.button color="blue" class="!px-6 !py-3 !rounded-2xl shadow-lg shadow-blue-500/20">
                        <iconify-icon icon="lucide:download" class="mr-2"></iconify-icon>
                        Export
                    </x-ui.button>
                </a>
            </div>
        </div>
    </div>

    {{-- Main Table Section --}}
    <div id="print-area">
        <x-ui.table :headers="[
            '#',
            'Kode',
            'Customer',
            'Status',
            'Pembayaran',
            'Total',
            'Kasir',
            'Tanggal',
            ['name' => 'Aksi', 'align' => 'center'],
        ]">
            @forelse ($orders as $item)
                <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-700/50 transition-colors">
                    <td data-label="#" class="px-6 py-5 text-sm font-bold text-neutral-400">
                        {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
                    </td>
                    <td data-label="Kode" class="px-6 py-5">
                        <span
                            class="text-sm font-black text-blue-600 dark:text-blue-400 tracking-tight">{{ $item->kode }}</span>
                    </td>
                    <td data-label="Customer" class="px-6 py-5 text-sm font-bold text-neutral-800 dark:text-neutral-200">
                        {{ $item->nama ?? '-' }}
                    </td>
                    <td data-label="Status" class="px-6 py-5">
                        <span
                            class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest
                            @if ($item->status === 'selesai') bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400
                            @elseif($item->status === 'diproses') bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400
                            @else bg-neutral-50 text-neutral-600 dark:bg-neutral-900/30 dark:text-neutral-400 @endif">
                            {{ $item->status }}
                        </span>
                    </td>
                    <td data-label="Pembayaran" class="px-6 py-5">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-neutral-500 dark:text-neutral-400">
                                {{ $item->paymentMethod ? $item->paymentMethod->nama_metode : 'Belum Bayar' }}
                            </span>
                        </div>
                    </td>
                    <td data-label="Total" class="px-6 py-5">
                        <span class="text-sm font-black text-neutral-800 dark:text-neutral-100 italic">
                            Rp {{ number_format($item->total - $item->discount_value, 0, ',', '.') }}
                        </span>
                    </td>
                    <td data-label="Kasir" class="px-6 py-5 text-sm font-medium text-neutral-500 dark:text-neutral-400">
                        {{ $item->user->name ?? '-' }}
                    </td>
                    <td data-label="Tanggal" class="px-6 py-5 text-sm font-bold text-neutral-500 dark:text-neutral-400">
                        {{ $item->created_at->format('d M, H:i') }}
                    </td>
                    <td data-label="Aksi" class="px-6 py-5">
                        <div class="flex items-center justify-center gap-3">
                            <x-ui.action-edit @click="$wire.editStatus('{{ base64_encode($item->id) }}')"
                                tooltip="Ubah Status" />
                            <x-ui.action-detail @click="$wire.showDetail('{{ base64_encode($item->id) }}')"
                                tooltip="Lihat Detail" />
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-6 py-20 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <iconify-icon icon="lucide:shopping-cart" class="text-6xl text-neutral-200"></iconify-icon>
                            <p class="text-neutral-400 font-bold tracking-widest uppercase text-xs">Belum ada transaksi
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
    </div>

    <div class="mt-8">
        {{ $orders->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    {{-- Edit Status Modal --}}
    <x-mdal name="edit-status-order">
        <div class="px-6 py-4">
            <h3 class="font-semibold text-lg text-center">Edit Status Pesanan</h3>

            @if ($selectedOrder)
                <div class="mt-4 space-y-4 text-sm text-neutral-800 dark:text-neutral-200">

                    {{-- Kode --}}
                    <div>
                        <label class="font-semibold ">Kode Pesanan</label>
                        <div class="mt-1 ">
                            {{ $selectedOrder->kode }}
                        </div>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="font-semibold ">Status</label>
                        <div class="relative">
                            <select wire:model="status"
                                class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                   bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200
                   px-3 py-2 text-sm focus:outline-none focus:ring-2
                   focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer appearance-none bg-none pr-10">
                                <option value="dibatalkan">Dibatalkan</option>
                                <option value="diproses">Diproses</option>
                                <option value="selesai">Selesai</option>
                            </select>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-neutral-400 pointer-events-none">expand_more</span>
                        </div>
                    </div>

                    {{-- Metode Pembayaran --}}
                    <div>
                        <label class="font-semibold ">Metode Pembayaran</label>
                        <div class="relative">
                            <select wire:model="metode_pembayaran"
                                class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                   bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200
                   px-3 py-2 text-sm focus:outline-none focus:ring-2
                   focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer appearance-none bg-none pr-10">
                                <option value="">Belum Bayar</option>
                                @foreach ($pembayaran as $pay)
                                    <option value="{{ $pay->id }}">{{ $pay->nama_metode }}</option>
                                @endforeach
                            </select>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-neutral-400 pointer-events-none">expand_more</span>
                        </div>
                    </div>

                </div>
            @endif

            {{-- Action --}}
            <div class="mt-6 flex justify-end gap-2">
                <button @click="$dispatch('close-modal', { name: 'edit-status-order' })"
                    class="px-4 py-2 text-sm rounded bg-red-600 text-white">
                    Batal
                </button>

                <button wire:click="updateStatus" wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm rounded bg-primary-600 text-white">
                    Update
                </button>
            </div>
        </div>
    </x-mdal>

    {{-- Detail Modal --}}
    <x-mdl name="detail-order">
        <div class="px-6 py-4">
            <h3 class="font-bold text-xl text-center mb-6 text-slate-800 dark:text-white">Detail Pesanan</h3>

            @if ($detailOrder)
                <div
                    class="text-sm space-y-3 p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 dark:text-slate-400">Kode</span>
                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $detailOrder->kode }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 dark:text-slate-400">Costumer</span>
                        <span
                            class="font-semibold text-slate-800 dark:text-slate-200">{{ $detailOrder->nama ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 dark:text-slate-400">Tanggal</span>
                        <span
                            class="font-semibold text-slate-800 dark:text-slate-200">{{ $detailOrder->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 dark:text-slate-400">Status</span>
                        <span
                            class="font-semibold text-slate-800 dark:text-slate-200">{{ ucfirst($detailOrder->status) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 dark:text-slate-400">Metode Pembayaran</span>
                        <span
                            class="font-semibold text-slate-800 dark:text-slate-200">{{ $detailOrder->paymentMethod ? $detailOrder->paymentMethod->nama_metode : 'Belum Bayar' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500 dark:text-slate-400">Code Discount</span>
                        <span
                            class="font-semibold text-slate-800 dark:text-slate-200">{{ $detailOrder->discount->kode_diskon ?? 'Tidak Ada' }}</span>
                    </div>
                </div>

                <div class="mt-6 border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-slate-600 dark:text-slate-300">
                            <thead
                                class="text-xs text-slate-700 uppercase bg-slate-100 dark:bg-slate-800 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Menu</th>
                                    <th class="px-4 py-3 font-semibold text-center w-16">Qty</th>
                                    <th class="px-4 py-3 font-semibold text-right">Harga</th>
                                    <th class="px-4 py-3 font-semibold text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                                @forelse ($detailOrder->items as $it)
                                    <tr
                                        class="bg-white dark:bg-slate-900/50 hover:bg-slate-50 dark:hover:bg-slate-800/80 transition">
                                        <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-200">
                                            <div class="flex flex-col">
                                                <span>{{ $it->menus->nama_menu }}</span>
                                                @if ($it->variants->isNotEmpty())
                                                    <div class="flex flex-wrap gap-1 mt-1">
                                                        @foreach ($it->variants as $variant)
                                                            <span
                                                                class="text-[9px] font-black text-blue-500 dark:text-blue-400 uppercase bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded-full border border-blue-100 dark:border-blue-800">
                                                                + {{ $variant->nama_varian }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">{{ $it->qty }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">Rp
                                            {{ number_format($it->harga_satuan, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">Rp
                                            {{ number_format($it->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-slate-500 italic">
                                            Tidak ada item</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <div
                        class="w-full sm:w-2/3 md:w-1/2 space-y-2 text-sm text-slate-600 dark:text-slate-300 bg-transparent">
                        <div class="flex justify-between px-2">
                            <span>Total Sebelum Diskon</span>
                            <span class="font-medium text-slate-800 dark:text-slate-200">Rp
                                {{ number_format($detailOrder->total ?? 0, 0, ',', '.') }}</span>
                        </div>

                        @if (($detailOrder->discount_value ?? 0) > 0)
                            <div class="flex justify-between px-2 text-red-500 dark:text-red-400">
                                <span>Diskon</span>
                                <span>- Rp {{ number_format($detailOrder->discount_value, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <div
                            class="pt-3 mt-2 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center px-2">
                            <span class="font-bold text-base text-slate-800 dark:text-white">Total Akhir</span>
                            <span class="font-bold text-lg text-primary-600 dark:text-primary-400">
                                Rp
                                {{ number_format(($detailOrder->total ?? 0) - ($detailOrder->discount_value ?? 0), 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-8 text-slate-500">
                    Memuat data pesanan...
                </div>
            @endif
        </div>
    </x-mdl>

    <script src="{{ asset('assets/js/flatpickr.js') }}" data-navigate-once></script>
    <script>
        function initTransaksiDateRangePicker() {
            const input = document.getElementById('transaksiDateRange');

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

        document.addEventListener('livewire:navigated', initTransaksiDateRangePicker);
        document.addEventListener('livewire:initialized', initTransaksiDateRangePicker);
        document.addEventListener('transaksi-date-range-reset', () => {
            const input = document.getElementById('transaksiDateRange');
            if (input && input._flatpickr) {
                input._flatpickr.clear();
            }
        });
        setTimeout(initTransaksiDateRangePicker, 80);

        function printSection(areaId) {
            const content = document.getElementById(areaId).innerHTML;
            const printWindow = window.open('', '', 'width=900,height=600');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Laporan Transaksi - Cash Coffee</title>
                        <style>
                            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap');
                            body { font-family: 'Inter', sans-serif; font-size: 10px; color: #171717; padding: 20px; }
                            table { border-collapse: collapse; width: 100%; border-radius: 12px; overflow: hidden; }
                            th { background: #f5f5f5; text-transform: uppercase; font-size: 8px; letter-spacing: 0.1em; padding: 10px 15px; text-align: left; }
                            td { border-bottom: 1px solid #f0f0f0; padding: 10px 15px; }
                            .font-black { font-weight: 900; }
                            .italic { font-style: italic; }
                            .text-blue-600 { color: #2563eb; }
                        </style>
                    </head>
                    <body>
                        <h1 style="text-align:center; font-weight:900; font-style:italic; font-size:24px; margin-bottom:0;">CASH COFFEE</h1>
                        <p style="text-align:center; font-size:10px; color:#666; margin-top:5px; margin-bottom:30px;">LAPORAN TRANSAKSI PENJUALAN</p>
                        ${content}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
            }, 500);
        }
    </script>
</div>
