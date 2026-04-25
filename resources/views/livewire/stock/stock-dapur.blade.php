<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Stok Dapur' }}</h6>
        <x-breadcrumb :title="$title ?? 'Stok Dapur'" />
    </div>
    <x-toast />

    {{-- Header Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div class="flex gap-2">
            <x-droppage perPage="{{ $perPage }}" />
            <div class="sm:w-[300px]">
                <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Cari bahan..."
                    class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.button-link href="/riwayat-stock/create" icon="mingcute:add-circle-line" color="blue">
                Tambah Stock
            </x-ui.button-link>
            <x-ui.button-link href="/stock-dapur/create" icon="mingcute:box-line" color="purple">
                Tambah Bahan
            </x-ui.button-link>
        </div>
    </div>

    <x-ui.table :headers="[
        'Nama Bahan',
        ['name' => 'Stok', 'align' => 'center'],
        'HPP',
        ['name' => 'Digunakan', 'align' => 'center'],
        'Satuan',
        ['name' => 'Aksi', 'align' => 'center'],
    ]">
        @forelse($items as $i)
            <tr wire:key="{{ $i->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition">
                <td class="px-6 py-4">
                    <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ $i->nama_bahan }}</span>
                </td>

                <td class="px-6 py-4 text-center">
                    <span
                        class="px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg text-sm font-bold border border-blue-100 dark:border-blue-700">
                        {{ number_format($i->stok, 0, ',', '.') }}
                    </span>
                </td>

                <td class="px-6 py-4">
                    <span class="font-bold text-neutral-900 dark:text-white">
                        Rp {{ number_format($i->hpp, 0, ',', '.') }}
                    </span>
                </td>

                <td class="px-6 py-4 text-center text-sm font-medium text-neutral-600 dark:text-neutral-400">
                    {{ number_format($i->digunakan ?? 0, 0, ',', '.') }}
                </td>

                <td class="px-6 py-4">
                    <span
                        class="text-sm border border-neutral-200 dark:border-neutral-700 px-2.5 py-1 rounded-lg bg-neutral-50 dark:bg-neutral-900 text-neutral-600 dark:text-neutral-400">
                        {{ $i->satuan->nama_satuan ?? '-' }}
                    </span>
                </td>

                <td class="px-6 py-4 text-center">
                    <div class="flex justify-center gap-2">
                        <x-ui.action-edit href="/stock-dapur/{{ base64_encode($i->id) }}/edit" wire:navigate />
                        <x-ui.action-delete
                            @click="$dispatch('open-modal', { name: 'confirm-delete', id: '{{ base64_encode($i->id) }}' })" />
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-12 text-neutral-500">
                    <div class="flex flex-col items-center justify-center gap-3">
                        <iconify-icon icon="mingcute:ghost-line" class="text-4xl"></iconify-icon>
                        <span class="text-sm">Tidak ada bahan ditemukan.</span>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-4">
        {{ $items->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    <x-mdal name="confirm-delete">
        <div class="px-6 py-6 text-center">
            <div
                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-rose-100 text-rose-600 shadow-sm border border-rose-200">
                <iconify-icon icon="lucide:alert-triangle" class="text-2xl"></iconify-icon>
            </div>

            <h3 class="mb-1 text-lg font-bold text-neutral-900 dark:text-neutral-100">Hapus Bahan Ini?</h3>
            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Menghapus bahan ini dapat mempengaruhi data HPP menu yang menggunakan bahan ini.
            </p>

            <div class="flex justify-center gap-3 border-t pt-6 border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'confirm-delete' })"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                    Batal
                </button>

                <x-ui.button type="button" color="danger"
                    @click="$wire.deleteIngredient(selectedId); $dispatch('close-modal', { name: 'confirm-delete' })"
                    class="!px-5 !py-2.5">
                    Ya, Hapus
                </x-ui.button>
            </div>
        </div>
    </x-mdal>
</div>
