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
                <x-input wire:model.live="search" place="Cari bahan..." />
            </div>
        </div>
        <div class="flex gap-2">
            <x-ui.button-link href="/gudang/create" icon="mingcute:add-circle-line">
                Tambah Bahan
            </x-ui.button-link>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table basic-border-table mb-2">
            <thead>
                <tr>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 w-10">#</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">Nama Bahan</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">Satuan</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-center">Stok
                    </th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-right">Harga
                        Satuan</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-center">Minimum
                        Stok</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-center">Action
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gudangs as $item)
                    <tr>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            {{ ($gudangs->currentPage() - 1) * $gudangs->perPage() + $loop->iteration }}
                        </td>

                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            <span class="font-semibold">{{ $item->nama_bahan }}</span>
                        </td>

                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-center">
                            {{ $item->satuan }}
                        </td>

                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-center">
                            {{ number_format($item->stok, 2, ',', '.') }}
                        </td>

                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-right">
                            Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}
                        </td>

                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-center">
                            {{ number_format($item->minimum_stok, 2, ',', '.') }}
                        </td>


                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-center">
                            <button
                                @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id)) }} })"
                                class="w-8 h-8 bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-400 rounded-full inline-flex items-center justify-center"
                                title="Hapus bahan">
                                <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">Tidak ada data bahan ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $gudangs->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    {{-- Modal Konfirmasi Hapus --}}
    <x-mdl>
        <div class="px-6 py-2 text-center">
            <h3 class="font-semibold text-lg">Hapus Data Bahan Ini?</h3>
            <p class="text-sm text-neutral-500 mt-1">Data gudang dan riwayat terkait akan ikut terhapus.</p>
        </div>
        <div class="flex justify-center gap-3 border-t border-neutral-200 p-4 dark:border-neutral-700">
            <button x-on:click="modalIsOpen = false"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-200 dark:hover:bg-neutral-600">
                Cancel
            </button>
            <button x-on:click="$wire.deleteGudang(selectedId); modalIsOpen = false"
                class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600">
                Delete
            </button>

        </div>
    </x-mdl>

</div>
