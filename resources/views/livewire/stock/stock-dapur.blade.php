<div>
    <x-toast />
    <div class="flex gap-2 mb-3">
        <x-droppage perPage="{{ $perPage }}" />
        <div class="sm:w-[300px]">
            <x-input wire:model.live="search" place="Cari Bahan..." />
        </div>
    </div>
    <div class="table-responsive">
        <table class="table basic-border-table mb-2">
            <thead>
                <tr>
                    <th class="border-r border-neutral-200 dark:border-neutral-600">Nama Bahan</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600">Stok</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600">digunakan</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600">Satuan</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($items as $i)
                <tr>
                    <td class="border-r border-neutral-200 dark:border-neutral-600">
                        {{ $i->nama_bahan }}
                    </td>

                    <td class="border-r border-neutral-200 dark:border-neutral-600">
                        {{ number_format($i->stok, 0, ',', '.') }}
                    </td>

                    <td class="border-r border-neutral-200 dark:border-neutral-600">
                        {{ number_format($i->digunakan ?? 0, 0, ',', '.') }}
                    </td>

                    <td class="border-r border-neutral-200 dark:border-neutral-600">
                        {{ $i->satuan->nama_satuan ?? '-' }}
                    </td>
                    <td class="border-r border-neutral-200 dark:border-neutral-600">
                        <a href="/stock-dapur/{{ base64_encode($i->id) }}/edit" wire:navigate class="w-8 h-8 bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 rounded-full inline-flex items-center justify-center">
                            <iconify-icon icon="lucide:edit"></iconify-icon>
                        </a>
                        <button @click="$dispatch('open-modal', @js([
                                        'name' => 'confirm-delete',
                                        'id' => base64_encode($i->id),
                                    ]))" class="w-8 h-8 bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-400 rounded-full inline-flex items-center justify-center">
                            <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-3 text-neutral-500">
                        Tidak ada data ditemukan...
                    </td>
                </tr>
                @endforelse
            </tbody>

        </table>

        {{-- PAGINATION --}}
        <div class="mt-3">
            {{ $items->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
        </div>
    </div>

    <x-mdl>
        <div class="px-6 py-2 text-center ">
            <h3 class="font-semibold text-lg">Hapus Bahan Ini?</h3>
        </div>
        <div class="flex justify-center gap-3 border-t border-neutral-200 p-4 dark:border-neutral-700">
            <button x-on:click="modalIsOpen = false" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-200 dark:hover:bg-neutral-600">
                Cancel
            </button>
            <button x-on:click="$wire.deleteIngredient(selectedId); modalIsOpen = false" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600">
                Delete
            </button>
        </div>
    </x-mdl>

</div>
