<div>
    <x-toast />
    <div class="flex justify-between items-center mb-4">
        <div class="flex gap-2">
            <x-droppage perPage="{{ $perPage }}" />
            <div class="sm:w-[300px] w-full">
                <x-input wire:model.live="search" place="Cari nama bahan..." />
            </div>
        </div>

        <div class="flex gap-2">
            <button wire:click="setFilter('semua')"
                class="px-4 py-1.5 rounded-full text-sm font-medium {{ $filterType === 'semua' ? 'bg-primary-600 text-white' : 'bg-neutral-200 dark:bg-neutral-700' }}">
                Semua
            </button>
            <button wire:click="setFilter('masuk')"
                class="px-4 py-1.5 rounded-full text-sm font-medium {{ $filterType === 'masuk' ? 'bg-success-600 text-white' : 'bg-neutral-200 dark:bg-neutral-700' }}">
                Masuk
            </button>
            <button wire:click="setFilter('keluar')"
                class="px-4 py-1.5 rounded-full text-sm font-medium {{ $filterType === 'keluar' ? 'bg-danger-600 text-white' : 'bg-neutral-200 dark:bg-neutral-700' }}">
                Keluar
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table basic-border-table mb-2">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Bahan</th>
                    <th>Tipe</th>
                    <th>Qty</th>
                    <th>H Satuan</th>
                    <th>Total</th>
                    <th>S Sebelum</th>
                    <th>S Sesudah</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($riwayats as $item)
                    <tr>
                        <td>{{ ($riwayats->currentPage() - 1) * $riwayats->perPage() + $loop->iteration }}</td>
                        <td>{{ $item->gudang->nama_bahan }}</td>
                        <td>
                            <span
                                class=" dark:bg-blue-600/25 text-danger-600 dark:text-danger-400 px-8 py-1.5 rounded-full font-medium text-sm
                                {{ $item->tipe === 'masuk' ? 'bg-success-100 text-success-700' : 'bg-danger-100 text-danger-700' }}">
                                {{ ucfirst($item->tipe) }}
                            </span>
                        </td>
                        <td>{{ number_format($item->jumlah, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item->total_harga, 0, ',', '.') }}</td>
                        <td>{{ number_format($item->stok_sebelum, 0, ',', '.') }}</td>
                        <td>{{ number_format($item->stok_sesudah, 0, ',', '.') }}</td>

                        <td>{{ $item->created_at->format('d/m/Y') }}</td>
                        <td class="text-center">
                            <a href="/gudang/{{ base64_encode($item->id) }}/edit" wire:navigate
                                class="w-8 h-8 bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 rounded-full inline-flex items-center justify-center"
                                title="Edit">
                                <iconify-icon icon="lucide:edit"></iconify-icon>
                            </a>
                            <button
                                @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id)) }} })"
                                class="w-8 h-8 bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-400 rounded-full inline-flex items-center justify-center"
                                title="Hapus">
                                <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4">Tidak ada data riwayat gudang.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        {{ $riwayats->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    {{-- Modal Delete --}}
    <x-mdl>
        <div class="px-6 py-2 text-center">
            <h3 class="font-semibold text-lg">Hapus Data Riwayat Ini?</h3>
            <p class="text-sm text-neutral-500 mt-1">Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="flex justify-center gap-3 border-t border-neutral-200 p-4 dark:border-neutral-700">
            <button x-on:click="modalIsOpen = false"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-200 dark:hover:bg-neutral-600">
                Cancel
            </button>
            <button x-on:click="$wire.deleteRiwayat(selectedId); modalIsOpen = false"
                class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600">
                Delete
            </button>
        </div>
    </x-mdl>
</div>
