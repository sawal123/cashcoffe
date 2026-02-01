<div>
    <x-toast />
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
            <div class="mb-1 text-sm font-medium  dark:text-neutral-300 flex flex-wrap items-center gap-4">
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
                                <button @click="$wire.editStatus('{{ base64_encode($item->id) }}')"
                                    class="w-8 h-8 bg-primary-100 text-primary-600 rounded-full inline-flex items-center justify-center"
                                    title="Edit Status">
                                    <iconify-icon icon="mingcute:edit-2-line"></iconify-icon>
                                </button>
                                <button @click="$wire.showDetail('{{ base64_encode($item->id) }}')"
                                    class="w-8 h-8 bg-danger-100 text-danger-600 rounded-full inline-flex items-center justify-center"
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

    <x-mdal name="edit-status-order">
        <div class="px-6 py-4">
            <h3 class="font-semibold text-lg text-center">Edit Status Pesanan</h3>

            @if ($selectedOrder)
                <div class="mt-4 space-y-4 text-sm">

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
                        <select wire:model="status"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700
               bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200
               px-3 py-2 text-sm focus:outline-none focus:ring-2
               focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer">
                            <option value="dibatalkan">Dibatalkan</option>
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </div>

                    {{-- Metode Pembayaran --}}
                    <div>
                        <label class="font-semibold ">Metode Pembayaran</label>
                        <select wire:model="metode_pembayaran"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700
               bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200
               px-3 py-2 text-sm focus:outline-none focus:ring-2
               focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer">
                            <option value="">Belum Bayar</option>
                            <option value="tunai">Tunai</option>
                            <option value="qris">QRIS</option>
                            <option value="transfer">Transfer</option>
                            <option value="komplemen">Komplemen</option>
                        </select>
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
        </x-mdl>


        <x-mdl name="detail-order">
            <div class="px-6 py-4">
                <h3 class="font-semibold text-lg text-center">Detail Pesanan</h3>

                @if ($detailOrder)
                    <div class="mt-4 text-sm space-y-2">

                        <div class="flex justify-between">
                            <span class="font-semibold ">Kode</span>
                            <span>{{ $detailOrder->kode }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="font-semibold ">Costumer</span>
                            <span>{{ $detailOrder->user->nama ?? '-' }}</span>
                        </div>



                        <div class="flex justify-between">
                            <span class="font-semibold ">Tanggal</span>
                            <span>{{ $detailOrder->created_at->format('d M Y H:i') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="font-semibold ">Status</span>
                            <span>{{ ucfirst($detailOrder->status) }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="font-semibold ">Metode Pembayaran</span>
                            <span>{{ ucfirst($detailOrder->metode_pembayaran ?? 'Belum Bayar') }}</span>
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
