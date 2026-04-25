<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Gudang' }}</h6>
        <x-breadcrumb :title="$title ?? 'Gudang'" />
    </div>

    <x-toast />
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div class="flex gap-2">
            <x-droppage perPage="{{ $perPage }}" />
            <div class="sm:w-[300px]">
                <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Cari bahan..." class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
            </div>
        </div>
        <div class="flex gap-2">
            <x-ui.button-link href="/gudang/create" icon="mingcute:add-circle-line">
                Tambah Bahan
            </x-ui.button-link>
        </div>
    </div>

    <x-ui.table :headers="[
        ['name' => '#', 'align' => 'center'],
        'Nama Bahan',
        'Satuan',
        ['name' => 'Stok', 'align' => 'center'],
        ['name' => 'Harga Satuan', 'align' => 'right'],
        ['name' => 'Min Stok', 'align' => 'center'],
        ['name' => 'Action', 'align' => 'center']
    ]">
        @forelse ($gudangs as $item)
            <tr wire:key="{{ $item->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition">
                <td class="px-6 py-4 text-center text-sm text-neutral-500">
                    {{ ($gudangs->currentPage() - 1) * $gudangs->perPage() + $loop->iteration }}
                </td>

                <td class="px-6 py-4">
                    <span class="font-bold text-neutral-800 dark:text-neutral-200">{{ $item->nama_bahan }}</span>
                </td>

                <td class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $item->satuan }}
                </td>

                <td class="px-6 py-4 text-center">
                    <span class="font-semibold text-neutral-900 dark:text-white">{{ number_format($item->stok, 2, ',', '.') }}</span>
                </td>

                <td class="px-6 py-4 text-right">
                    <span class="font-bold text-neutral-900 dark:text-white">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</span>
                </td>

                <td class="px-6 py-4 text-center">
                    <span class="px-2 py-1 rounded-lg {{ $item->stok < $item->minimum_stok ? 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400' }} text-xs font-bold">
                        {{ number_format($item->minimum_stok, 2, ',', '.') }}
                    </span>
                </td>

                <td class="px-6 py-4">
                    <div class="flex justify-center gap-2">
                        <x-ui.action-edit href="/gudang/{{ base64_encode($item->id) }}/edit" wire:navigate />
                        <x-ui.action-delete @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id)) }} })" />
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-12 text-neutral-500">
                    <div class="flex flex-col items-center justify-center gap-3">
                        <iconify-icon icon="mingcute:ghost-line" class="text-4xl"></iconify-icon>
                        <span class="text-sm">Tidak ada data bahan ditemukan.</span>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-4">
        {{ $gudangs->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    {{-- Modal Konfirmasi Hapus --}}
    <x-mdal name="confirm-delete">
        <div class="px-6 py-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-rose-100 text-rose-600 shadow-sm border border-rose-200">
                <iconify-icon icon="lucide:alert-triangle" class="text-2xl"></iconify-icon>
            </div>

            <h3 class="mb-1 text-lg font-bold text-neutral-900 dark:text-neutral-100">Hapus Data Bahan Ini?</h3>
            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Data gudang dan riwayat terkait akan ikut terhapus. Tindakan ini tidak dapat dibatalkan.
            </p>

            <div class="flex justify-center gap-3 border-t pt-6 border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'confirm-delete' })"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                    Batal
                </button>

                <x-ui.button type="button" color="danger" @click="$wire.deleteGudang(selectedId); $dispatch('close-modal', { name: 'confirm-delete' })" class="!px-5 !py-2.5">
                    Ya, Hapus
                </x-ui.button>
            </div>
        </div>
    </x-mdal>

</div>
