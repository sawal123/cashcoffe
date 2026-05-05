<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Pesanan' }}</h6>
        <x-breadcrumb :title="$title ?? 'Pesanan'" />
    </div>
    <x-toast />
    
    {{-- Summary Bar --}}
    @if (!empty($totalPerMetode))
    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
        @foreach ($totalPerMetode as $metode => $total)
            <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-4 rounded-3xl shadow-sm">
                <p class="text-xs font-bold text-neutral-500 uppercase tracking-widest mb-1">{{ ucfirst($metode ?? 'Belum Bayar') }}</p>
                <p class="text-lg font-bold text-neutral-900 dark:text-white">Rp{{ number_format($total, 0, ',', '.') }}</p>
            </div>
        @endforeach
        <div class="bg-blue-600 p-4 rounded-3xl shadow-lg shadow-blue-500/30 col-span-2 md:col-span-1 lg:col-span-1 ml-auto w-full md:w-auto min-w-[200px]">
            <p class="text-xs font-bold text-blue-100 uppercase tracking-widest mb-1">Total Omset</p>
            <p class="text-lg font-bold text-white">Rp{{ number_format($totalOmset, 0, ',', '.') }}</p>
        </div>
    </div>
    @endif

    {{-- Header Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div class="flex gap-2">
            <x-droppage perPage="{{ $perPage }}" />
            <div class="sm:w-[300px]">
                <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Cari pesanan..." class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
            </div>
        </div>
        <div class="flex gap-2">
            <x-ui.button-link href="/order/create" icon="mingcute:add-circle-line">
                Pesan Menu
            </x-ui.button-link>
        </div>
    </div>

    <x-ui.table :headers="[
        ['name' => '#', 'align' => 'center'],
        'Kode',
        'Nama Pelanggan',
        ['name' => 'Status', 'align' => 'center'],
        'Metode',
        'Total',
        'Kasir',
        'Waktu',
        ['name' => 'Action', 'align' => 'center']
    ]">
        @forelse ($orders as $item)
            <tr wire:key="{{ $item->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition">
                <td data-label="#" class="px-4 sm:px-6 py-4 text-center text-sm text-neutral-500">
                    {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
                </td>
                <td data-label="Kode" class="px-4 sm:px-6 py-4">
                    <span class="font-mono text-sm font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2 py-1 rounded-lg">#{{ $item->kode }}</span>
                </td>
                <td data-label="Pelanggan" class="px-4 sm:px-6 py-4">
                    <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ $item->nama ?: '-' }}</span>
                </td>
                <td data-label="Status" class="px-4 sm:px-6 py-4 text-center">
                    @php
                        $statusClasses = [
                            'selesai' => 'bg-green-100 text-green-700 border-green-200',
                            'diproses' => 'bg-amber-100 text-amber-700 border-amber-200',
                            'batal' => 'bg-rose-100 text-rose-700 border-rose-200',
                        ];
                        $statusClass = $statusClasses[$item->status] ?? 'bg-neutral-100 text-neutral-700 border-neutral-200';
                    @endphp
                    <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border {{ $statusClass }}">
                        {{ ucwords(str_replace('_', ' ', $item->status)) }}
                    </span>
                </td>
                <td data-label="Metode" class="px-4 sm:px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $item->metode_pembayaran ? ucwords($item->metode_pembayaran) : 'Belum Bayar' }}
                </td>
                <td data-label="Total" class="px-4 sm:px-6 py-4">
                    <span class="font-bold text-neutral-900 dark:text-white">Rp{{ number_format($item->total - $item->discount_value, 0, ',', '.') }}</span>
                </td>
                <td data-label="Kasir" class="px-4 sm:px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $item->user->name ?? '-' }}
                </td>
                <td data-label="Waktu" class="px-4 sm:px-6 py-4 text-xs text-neutral-500">
                    {{ $item->created_at->format('d/m/y H:i') }}
                </td>
                <td data-label="Aksi" class="px-4 sm:px-6 py-4 text-center">
                    <div class="flex justify-center gap-1.5">

                        {{-- ======================= --}}
                        {{-- STATUS: DIPROSES --}}
                        {{-- ======================= --}}
                        @if ($item->status === 'diproses')
                            {{-- Semua role bisa: Selesai --}}
                            <button wire:click="saji('{{ base64_encode($item->id) }}')"
                                wire:loading.attr="disabled" wire:target="saji('{{ base64_encode($item->id) }}')"
                                title="Tandai Selesai"
                                class="w-8 h-8 rounded-xl bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center hover:bg-green-600 hover:text-white transition-all">
                                <iconify-icon icon="mingcute:check-line" class="text-sm"></iconify-icon>
                            </button>

                            {{-- Semua role bisa: Print --}}
                            <a href="{{ route('struk.print', base64_encode($item->id)) }}" target="_blank"
                                title="Print Struk"
                                class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all">
                                <iconify-icon icon="lucide:printer" class="text-sm"></iconify-icon>
                            </a>

                            {{-- Semua role bisa: Edit --}}
                            <a href="/order/{{ base64_encode($item->id) }}/edit" wire:navigate
                                title="Edit Pesanan"
                                class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center hover:bg-amber-600 hover:text-white transition-all">
                                <iconify-icon icon="mingcute:edit-line" class="text-sm"></iconify-icon>
                            </a>

                            {{-- Hanya Superadmin: Delete --}}
                            @hasrole('superadmin')
                                <button
                                    @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id)) }} })"
                                    title="Hapus Pesanan"
                                    class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all">
                                    <iconify-icon icon="mingcute:delete-2-line" class="text-sm"></iconify-icon>
                                </button>
                            @endhasrole

                        {{-- ======================= --}}
                        {{-- STATUS: SELESAI --}}
                        {{-- ======================= --}}
                        @elseif ($item->status === 'selesai')
                            {{-- Semua role bisa: Detail --}}
                            <button wire:click="showDetail('{{ base64_encode($item->id) }}')"
                                title="Lihat Detail"
                                class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all">
                                <iconify-icon icon="mingcute:eye-line" class="text-sm"></iconify-icon>
                            </button>

                            {{-- Semua role bisa: Print --}}
                            <a href="{{ route('struk.print', base64_encode($item->id)) }}" target="_blank"
                                title="Print Struk"
                                class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all">
                                <iconify-icon icon="lucide:printer" class="text-sm"></iconify-icon>
                            </a>

                            {{-- Hanya Superadmin: Edit --}}
                            @hasrole('superadmin')
                                <a href="/order/{{ base64_encode($item->id) }}/edit" wire:navigate
                                    title="Edit Pesanan"
                                    class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center hover:bg-amber-600 hover:text-white transition-all">
                                    <iconify-icon icon="mingcute:edit-line" class="text-sm"></iconify-icon>
                                </a>
                            @endhasrole

                        {{-- ======================= --}}
                        {{-- STATUS: DIBATALKAN --}}
                        {{-- ======================= --}}
                        @elseif ($item->status === 'dibatalkan' || $item->status === 'batal')
                            {{-- Semua role bisa: Detail --}}
                            <button wire:click="showDetail('{{ base64_encode($item->id) }}')"
                                title="Lihat Detail"
                                class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all">
                                <iconify-icon icon="mingcute:eye-line" class="text-sm"></iconify-icon>
                            </button>

                            {{-- Hanya Superadmin: Edit, Delete, Print --}}
                            @hasrole('superadmin')
                                <a href="/order/{{ base64_encode($item->id) }}/edit" wire:navigate
                                    title="Edit Pesanan"
                                    class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center hover:bg-amber-600 hover:text-white transition-all">
                                    <iconify-icon icon="mingcute:edit-line" class="text-sm"></iconify-icon>
                                </a>
                                <a href="{{ route('struk.print', base64_encode($item->id)) }}" target="_blank"
                                    title="Print Struk"
                                    class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all">
                                    <iconify-icon icon="lucide:printer" class="text-sm"></iconify-icon>
                                </a>
                                <button
                                    @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id)) }} })"
                                    title="Hapus Pesanan"
                                    class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all">
                                    <iconify-icon icon="mingcute:delete-2-line" class="text-sm"></iconify-icon>
                                </button>
                            @endhasrole
                        @endif

                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center py-12 text-neutral-500">
                    <div class="flex flex-col items-center justify-center gap-3">
                        <iconify-icon icon="mingcute:ghost-line" class="text-4xl"></iconify-icon>
                        <span class="text-sm">Tidak ada pesanan ditemukan.</span>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-4">
        {{ $orders->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    {{-- ============================================ --}}
    {{-- MODAL: DETAIL PESANAN --}}
    {{-- ============================================ --}}
    <x-mdal name="detail-order">
        @if ($detailOrder ?? null)
            <div class="px-6 py-6">
                {{-- Header --}}
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-neutral-100 dark:border-neutral-700">
                    <div>
                        <span class="text-[10px] font-black text-neutral-400 uppercase tracking-widest block mb-1">Detail Pesanan</span>
                        <h3 class="text-xl font-black text-neutral-900 dark:text-neutral-100">
                            #{{ $detailOrder->kode }}
                        </h3>
                    </div>
                    <div class="text-right">
                        @php
                            $sc = match($detailOrder->status) {
                                'selesai'    => 'bg-green-100 text-green-700 border-green-200',
                                'diproses'   => 'bg-amber-100 text-amber-700 border-amber-200',
                                'dibatalkan','batal' => 'bg-rose-100 text-rose-700 border-rose-200',
                                default      => 'bg-neutral-100 text-neutral-700 border-neutral-200',
                            };
                        @endphp
                        <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border {{ $sc }}">
                            {{ ucwords($detailOrder->status) }}
                        </span>
                        <p class="text-xs text-neutral-400 mt-2">{{ $detailOrder->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>

                {{-- Info Row --}}
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div class="bg-neutral-50 dark:bg-neutral-900 rounded-2xl p-3">
                        <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest block mb-1">Pelanggan</span>
                        <span class="text-sm font-bold text-neutral-800 dark:text-neutral-200">{{ $detailOrder->nama ?: '-' }}</span>
                    </div>
                    <div class="bg-neutral-50 dark:bg-neutral-900 rounded-2xl p-3">
                        <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest block mb-1">Metode Bayar</span>
                        <span class="text-sm font-bold text-neutral-800 dark:text-neutral-200">{{ ucwords($detailOrder->metode_pembayaran ?? '-') }}</span>
                    </div>
                    <div class="bg-neutral-50 dark:bg-neutral-900 rounded-2xl p-3">
                        <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest block mb-1">Kasir</span>
                        <span class="text-sm font-bold text-neutral-800 dark:text-neutral-200">{{ $detailOrder->user->name ?? '-' }}</span>
                    </div>
                    <div class="bg-neutral-50 dark:bg-neutral-900 rounded-2xl p-3">
                        <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest block mb-1">Diskon</span>
                        <span class="text-sm font-bold text-green-600">-Rp{{ number_format($detailOrder->discount_value ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Item List --}}
                <div class="mb-5">
                    <h4 class="text-xs font-black text-neutral-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <iconify-icon icon="mingcute:list-check-line" class="text-blue-600 text-base"></iconify-icon>
                        Item Pesanan
                    </h4>
                    <div class="space-y-2 max-h-[240px] overflow-y-auto pr-1">
                        @foreach ($selectedOrderItems as $oi)
                            <div class="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-900 rounded-2xl border border-neutral-100 dark:border-neutral-700">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-neutral-800 dark:text-neutral-200 line-clamp-1">
                                        {{ $oi->menus->nama_menu ?? 'Menu Dihapus' }}
                                    </p>
                                    @if ($oi->variants && $oi->variants->count() > 0)
                                        <p class="text-[10px] text-neutral-400 italic">
                                            {{ $oi->variants->pluck('nama_opsi')->filter()->join(', ') }}
                                        </p>
                                    @endif
                                    <p class="text-xs text-neutral-400 mt-0.5">
                                        Rp{{ number_format($oi->harga_satuan ?? 0, 0, ',', '.') }} × {{ $oi->qty }}
                                    </p>
                                </div>
                                <span class="text-sm font-black text-neutral-900 dark:text-white ml-3">
                                    Rp{{ number_format($oi->subtotal ?? ($oi->harga_satuan * $oi->qty), 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Footer Total --}}
                <div class="border-t-2 border-dashed border-neutral-100 dark:border-neutral-700 pt-4 space-y-2">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-neutral-400 font-bold uppercase tracking-widest text-[10px]">Subtotal</span>
                        <span class="font-bold text-neutral-700 dark:text-neutral-300">Rp{{ number_format($detailOrder->total ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-neutral-400 font-bold uppercase tracking-widest text-[10px]">Diskon</span>
                        <span class="font-bold text-green-600">-Rp{{ number_format($detailOrder->discount_value ?? 0, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-neutral-100 dark:border-neutral-700">
                        <span class="font-black text-neutral-700 dark:text-neutral-300 uppercase text-sm">Total Akhir</span>
                        <span class="text-xl font-black text-blue-700 dark:text-blue-400">
                            Rp{{ number_format(max(0, ($detailOrder->total ?? 0) - ($detailOrder->discount_value ?? 0)), 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- Action Footer --}}
                <div class="flex gap-3 mt-6 pt-4 border-t border-neutral-100 dark:border-neutral-700">
                    <button type="button" x-on:click="$dispatch('close-modal', { name: 'detail-order' })"
                        class="flex-1 px-5 py-3 rounded-2xl border border-neutral-300 bg-white dark:bg-neutral-800 dark:border-neutral-600 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 text-sm font-bold transition">
                        Tutup
                    </button>
                    <a href="{{ route('struk.print', base64_encode($detailOrder->id)) }}" target="_blank"
                        class="flex-1 flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 transition shadow-lg shadow-blue-500/20">
                        <iconify-icon icon="lucide:printer" class="text-base"></iconify-icon>
                        Print Struk
                    </a>
                </div>
            </div>
        @endif
    </x-mdal>

    {{-- ============================================ --}}
    {{-- MODAL: KONFIRMASI HAPUS --}}
    {{-- ============================================ --}}
    <x-mdal name="confirm-delete">
        <div class="px-6 py-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-rose-100 text-rose-600 shadow-sm border border-rose-200">
                <iconify-icon icon="lucide:alert-triangle" class="text-2xl"></iconify-icon>
            </div>

            <h3 class="mb-1 text-lg font-bold text-neutral-900 dark:text-neutral-100">Hapus Pesanan Ini?</h3>
            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Tindakan ini tidak dapat dibatalkan. Data pesanan akan dihapus dari sistem.
            </p>

            <div class="flex justify-center gap-3 border-t pt-6 border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'confirm-delete' })"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                    Batal
                </button>

                <x-ui.button type="button" color="danger" @click="$wire.delPesanan(selectedId); $dispatch('close-modal', { name: 'confirm-delete' })" class="!px-5 !py-2.5">
                    Ya, Hapus
                </x-ui.button>
            </div>
        </div>
    </x-mdal>
</div>

