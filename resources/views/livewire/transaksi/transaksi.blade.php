<div>
    <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 mb-4">
        {{-- Search --}}
        <x-droppage perPage="{{ $perPage }}" />
        <div class="w-64 min-w-[200px]">
            <x-input wire:model.live="search" class="mt-2" place="Cari..." />
        </div>
        {{-- Filter Pembayaran --}}
        <div class="w-52 min-w-[180px]">
            <select id="metode_pembayaran" wire:model.live="filterPembayaran"
                class="w-full form-select rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200">
                <option value="" class="text-neutral-500">..Pembayaran..</option>
                @foreach ($pembayaran as $pay)
                    <option value="{{ $pay }}" class="text-neutral-800 dark:text-neutral-200">
                        {{ $pay }}
                    </option>
                @endforeach
            </select>
        </div>
        {{-- Filter Tanggal --}}
        <div class="flex items-center gap-2">
            <input type="date" wire:model.live="dateFrom"
                class="rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2
            bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200 w-40 min-w-[140px]">
            <span>-</span>
            <input type="date" wire:model.live="dateTo"
                class="rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2
            bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200 w-40 min-w-[140px]">
        </div>
    </div>
    {{-- print --}}

    {{-- Tombol Print --}}
    <x-b-blue onclick="printSection('print-area')">
        🖨️ Print
    </x-b-blue>
    <x-a url="{{ route('orders.export') }}" active='secondary'>📊 Export Excel</x-a>
    {{-- <a href="{{ route('orders.export') }}"
        class="bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded">
        📊 Export Excel
    </a> --}}
    <div class="" id="print-area">
        @if (!empty($totalPerMetode))
            <div
                class="mb-1 text-sm font-medium text-neutral-700 dark:text-neutral-300 flex flex-wrap items-center gap-4">
                @foreach ($totalPerMetode as $metode => $total)
                    <span>{{ ucfirst($metode ?? 'Belum Bayar') }}:
                        <span class="font-semibold text-green-600 dark:text-green-400">
                            Rp{{ number_format($total, 0, ',', '.') }}
                        </span>
                    </span>
                @endforeach
                <span class="ml-auto font-semibold text-blue-600 dark:text-blue-400">
                    Total Omset: Rp{{ number_format($totalOmset, 0, ',', '.') }}
                </span>
            </div>
            <div class="fs" style="font-size: 11px">Hanya pesanan selesai yang dihitung | Kompelemen tidak
                ditotalkan di
                omset
            </div>
        @endif

        {{-- Tabel --}}
        <div class="table-responsive mt-2">
            <table class="table basic-border-table mb-2 w-full text-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Status</th>
                        <th>Pembayaran</th>
                        <th>Total</th>
                        <th>Kasir</th>
                        <th>Create</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $item)
                        <tr>
                            <td>{{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}</td>
                            <td>{{ $item->kode }}</td>
                            <td>{{ $item->nama ?? '-' }}</td>
                            <td>
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-medium
                            @if ($item->status === 'selesai') bg-danger-100 dark:bg-blue-600/25 text-green-700
                            @elseif($item->status === 'diproses') bg-danger-100 dark:bg-blue-600/25
                            @else bg-danger-100 dark:bg-blue-600/25 @endif">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td>{{ $item->metode_pembayaran ? ucfirst($item->metode_pembayaran) : 'Belum Bayar' }}</td>
                            <td>Rp {{ number_format($item->total - $item->discount_value, 0, ',', '.') }}</td>
                            <td>{{ $item->user->name ?? '-' }}</td>
                            <td>{{ $item->created_at->format('d M Y | H:i') }}</td>
                            <td>
                                <button @click="$wire.showDetail('{{ base64_encode($item->id) }}')"
                                    class="w-8 h-8 bg-primary-100 text-primary-600 rounded-full inline-flex items-center justify-center"
                                    title="Detail Pesanan">
                                    <iconify-icon icon="mingcute:eye-2-line"></iconify-icon>
                                </button>

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">Tidak ada data ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $orders->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    <x-mdl name="detail-order">
        <div class="px-6 py-4">
            <h3 class="font-semibold text-lg text-center">Detail Pesanan</h3>

            @if ($selectedOrder)
                <div class="mt-4 text-sm space-y-2">

                    <div class="flex justify-between">
                        <span class="font-semibold text-neutral-700">Kode</span>
                        <span>{{ $selectedOrder->kode }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="font-semibold text-neutral-700">Costumer</span>
                        <span>{{ $selectedOrder->nama ?? '-' }}</span>
                    </div>



                    <div class="flex justify-between">
                        <span class="font-semibold text-neutral-700">Tanggal</span>
                        <span>{{ $selectedOrder->created_at->format('d M Y H:i') }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="font-semibold text-neutral-700">Status</span>
                        <span>{{ ucfirst($selectedOrder->status) }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="font-semibold text-neutral-700">Metode Pembayaran</span>
                        <span>{{ ucfirst($selectedOrder->metode_pembayaran ?? 'Belum Bayar') }}</span>
                    </div>

                </div>
            @endif



            <div class="mt-5 border-t pt-3 table-responsive">
                <table class="table basic-border-table mb-2 w-full text-sm">
                    <thead class="border-b">
                        <tr>
                            <th class="py-2 text-left">Menu</th>
                            {{-- <th class="py-2 text-left">Varian</th> --}}
                            <th class="py-2 text-left">Qty</th>
                            <th class="py-2 text-left">Harga</th>
                            <th class="py-2 text-left">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($selectedOrderItems as $it)
                            <tr class="border-b">
                                <td class="py-2">{{ $it->menus->nama_menu }}</td>
                                {{-- <td>{{ $it->varian->nama ?? '-' }}</td> --}}
                                <td>{{ $it->qty }}</td>
                                <td>Rp {{ number_format($it->harga_satuan, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($it->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-3">Tidak ada item</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>

            <div class="mt-4 p-3 text-end  rounded">
                <p><strong>Total:</strong> Rp
                    {{ number_format(($selectedOrder['total'] ?? 0) - ($selectedOrder['discount_value'] ?? 0), 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- <div class="flex justify-center gap-3 border-t p-4">
            <button x-on:click="modalIsOpen = false"
                class="px-4 py-2 rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">
                Tutup
            </button>
        </div> --}}
    </x-mdl>
    <script>
        function printSection(areaId) {
            const content = document.getElementById(areaId).innerHTML;
            const printWindow = window.open('', '', 'width=900,height=600');
            printWindow.document.write(`
            <html>
                <head>
                    <title>Cetak Laporan Transaksi</title>
                    <style>
                        body { font-family: sans-serif; font-size: 12px; color: #333; }
                        table { border-collapse: collapse; width: 100%; }
                        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
                        th { background: #f0f0f0; }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
            </html>
        `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        }
    </script>




</div>
