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
            <button wire:click="setFilter('in')"
                class="px-4 py-1.5 rounded-full text-sm font-medium {{ $filterType === 'in' ? 'bg-primary-600 text-white' : 'bg-neutral-200 dark:bg-neutral-700' }}">
                Masuk
            </button>
            <button wire:click="setFilter('out')"
                class="px-4 py-1.5 rounded-full text-sm font-medium {{ $filterType === 'out' ? 'bg-primary-600 text-white' : 'bg-neutral-200 dark:bg-neutral-700'}}">
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
                    <th>Sebelum</th>
                    <th>Sesudah</th>
                    <th>Keterangan</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($riwayats as $item)
                    <tr>
                        <td>{{ ($riwayats->currentPage() - 1) * $riwayats->perPage() + $loop->iteration }}</td>

                        <td>{{ $item->ingredient->nama_bahan }}</td>

                        <td>
                            <span
                                class="px-3 py-1.5 rounded-full text-sm font-medium
                                {{ $item->tipe === 'in' ? 'bg-success-500 text-success-700' : 'bg-danger-500 text-danger-700' }}">
                                {{ $item->tipe === 'in' ? 'Masuk' : 'Keluar' }}
                            </span>
                        </td>

                        <td>{{ number_format($item->qty, 2, ',', '.') }}</td>

                        <td>{{ number_format($item->qty_before, 2, ',', '.') }}</td>

                        <td>{{ number_format($item->qty_after, 2, ',', '.') }}</td>

                        <td>{{ $item->keterangan ?? '-' }}</td>

                        <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>

                        <td class="text-center">
                            <button
                                @click="$dispatch('open-modal', { name: 'confirm-delete', id: '{{ base64_encode($item->id) }}' })"
                                class="w-8 h-8 bg-danger-100 text-danger-600 rounded-full inline-flex items-center justify-center">
                                <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4">Tidak ada riwayat stok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>
    {{ $riwayats->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}

    {{-- Modal Delete --}}
    <x-mdl>
        <div class="px-6 py-2 text-center">
            <h3 class="font-semibold text-lg">Hapus Data Riwayat Ini?</h3>
            <p class="text-sm text-neutral-500 mt-1">Tindakan ini tidak dapat dibatalkan.</p>
        </div>

        <div class="flex justify-center gap-3 border-t p-4">
            <button x-on:click="modalIsOpen = false" class="border px-4 py-2 rounded-md bg-neutral-200">
                Cancel
            </button>

            <button x-on:click="$wire.deleteRiwayat(selectedId); modalIsOpen = false"
                class="bg-red-600 text-white px-4 py-2 rounded-md">
                Delete
            </button>
        </div>
    </x-mdl>
</div>
