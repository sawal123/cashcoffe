<div>
    <x-toast />
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div class="flex gap-2">
            <x-droppage perPage="{{ $perPage }}" />
            <div class="sm:w-[300px] w-full">
                <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Cari nama bahan..." class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
            </div>
        </div>

        <div class="flex p-1 bg-neutral-100 dark:bg-neutral-800 rounded-xl gap-1">
            <button wire:click="setFilter('semua')"
                class="px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $filterType === 'semua' ? 'bg-white dark:bg-neutral-700 shadow-sm text-blue-600' : 'text-neutral-500 hover:text-neutral-700' }}">
                SEMUA
            </button>
            <button wire:click="setFilter('masuk')"
                class="px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $filterType === 'masuk' ? 'bg-white dark:bg-neutral-700 shadow-sm text-emerald-600' : 'text-neutral-500 hover:text-neutral-700' }}">
                MASUK
            </button>
            <button wire:click="setFilter('keluar')"
                class="px-4 py-2 rounded-lg text-xs font-bold transition-all {{ $filterType === 'keluar' ? 'bg-white dark:bg-neutral-700 shadow-sm text-rose-600' : 'text-neutral-500 hover:text-neutral-700' }}">
                KELUAR
            </button>
        </div>
    </div>

    <x-ui.table :headers="[
        ['name' => '#', 'align' => 'center'],
        'Nama Bahan',
        ['name' => 'Tipe', 'align' => 'center'],
        ['name' => 'Qty', 'align' => 'center'],
        ['name' => 'H Satuan', 'align' => 'right'],
        ['name' => 'Total', 'align' => 'right'],
        ['name' => 'S Sebelum', 'align' => 'center'],
        ['name' => 'S Sesudah', 'align' => 'center'],
        'Tanggal',
        ['name' => 'Action', 'align' => 'center']
    ]">
        @forelse ($riwayats as $item)
            <tr wire:key="{{ $item->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition">
                <td data-label="#" class="px-6 py-4 text-center text-sm text-neutral-500">
                    {{ ($riwayats->currentPage() - 1) * $riwayats->perPage() + $loop->iteration }}
                </td>
                <td data-label="Nama Bahan" class="px-6 py-4 font-bold text-neutral-800 dark:text-neutral-200">
                    {{ $item->gudang->nama_bahan }}
                </td>
                <td data-label="Tipe" class="px-6 py-4 text-center">
                    @if ($item->tipe === 'masuk')
                        <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100 text-[10px] font-black uppercase tracking-widest">
                            MASUK
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full bg-rose-50 text-rose-600 border border-rose-100 text-[10px] font-black uppercase tracking-widest">
                            KELUAR
                        </span>
                    @endif
                </td>
                <td data-label="Qty" class="px-6 py-4 text-center font-bold">
                    {{ number_format($item->jumlah, 0, ',', '.') }}
                </td>
                <td data-label="H Satuan" class="px-6 py-4 text-right text-sm text-neutral-600">
                    Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}
                </td>
                <td data-label="Total" class="px-6 py-4 text-right font-bold text-neutral-900 dark:text-white">
                    Rp {{ number_format($item->total_harga, 0, ',', '.') }}
                </td>
                <td data-label="S Sebelum" class="px-6 py-4 text-center text-sm text-neutral-500">
                    {{ number_format($item->stok_sebelum, 0, ',', '.') }}
                </td>
                <td data-label="S Sesudah" class="px-6 py-4 text-center text-sm text-neutral-500">
                    {{ number_format($item->stok_sesudah, 0, ',', '.') }}
                </td>
                <td data-label="Tanggal" class="px-6 py-4 text-sm text-neutral-500">
                    {{ $item->created_at->format('d M Y') }}
                </td>
                <td data-label="Aksi" class="px-6 py-4">
                    <div class="flex justify-center gap-2">
                        <x-ui.action-edit href="/gudang/{{ base64_encode($item->id) }}/edit" wire:navigate />
                        <x-ui.action-delete @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id) ) }} })" />
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center py-12 text-neutral-500">
                    <div class="flex flex-col items-center justify-center gap-3">
                        <iconify-icon icon="mingcute:ghost-line" class="text-4xl"></iconify-icon>
                        <span class="text-sm">Tidak ada data riwayat gudang.</span>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-4">
        {{ $riwayats->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    {{-- Modal Delete --}}
    <x-mdal name="confirm-delete">
        <div class="px-6 py-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-rose-100 text-rose-600 shadow-sm border border-rose-200">
                <iconify-icon icon="lucide:alert-triangle" class="text-2xl"></iconify-icon>
            </div>

            <h3 class="mb-1 text-lg font-bold text-neutral-900 dark:text-neutral-100">Hapus Data Riwayat Ini?</h3>
            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Tindakan ini tidak dapat dibatalkan. Data riwayat akan dihapus secara permanen.
            </p>

            <div class="flex justify-center gap-3 border-t pt-6 border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'confirm-delete' })"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                    Batal
                </button>

                <x-ui.button type="button" color="danger" @click="$wire.deleteRiwayat(selectedId); $dispatch('close-modal', { name: 'confirm-delete' })" class="!px-5 !py-2.5">
                    Ya, Hapus
                </x-ui.button>
            </div>
        </div>
    </x-mdal>
</div>
