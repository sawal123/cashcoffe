<div>
    <x-toast />

    <div class="flex gap-2 mb-3">
        <x-droppage perPage="{{ $perPage }}" />
        <div class="sm:w-[300px]">
            <x-input wire:model.live="search" place="Cari pengeluaran..." />
        </div>
    </div>

    <div class="table-responsive">
        <table class="table basic-border-table mb-2">
            <thead>
                <tr>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 w-10">#</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600">Tanggal</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600">Kategori</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600">Deskripsi</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 text-center">Satuan</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 text-right">Total</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 text-center">Metode</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 text-center">Bukti</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pengeluarans as $item)
                    <tr>
                        <td class="border-r border-neutral-200 dark:border-neutral-600">
                            {{ ($pengeluarans->currentPage() - 1) * $pengeluarans->perPage() + $loop->iteration }}
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600">
                            {{ \Carbon\Carbon::parse($item->tanggal_pengeluaran)->translatedFormat('d M Y') }}
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600">
                            {{ $item->kategori ?? '-' }}
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600">
                            {{ $item->title }}
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 text-center">
                            {{ $item->satuan ?? '-' }}
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 text-right">
                            Rp {{ number_format($item->total, 0, ',', '.') }}
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 text-center">
                            {{ $item->metode_pembayaran ?? '-' }}
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 text-center">
                            @if ($item->bukti)
                                <a href="{{ Storage::url($item->bukti) }}" target="_blank"
                                    class="text-primary-600 hover:underline text-sm">Lihat</a>
                            @else
                                <span class="text-neutral-400">-</span>
                            @endif
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 text-center">
                            <button
                                @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id)) }} })"
                                class="w-8 h-8 bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-400 rounded-full inline-flex items-center justify-center"
                                title="Hapus pengeluaran">
                                <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">Tidak ada data pengeluaran ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $pengeluarans->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    {{-- Modal Konfirmasi Hapus --}}
    <x-mdl>
        <div class="px-6 py-2 text-center">
            <h3 class="font-semibold text-lg">Hapus Data Pengeluaran Ini?</h3>
            <p class="text-sm text-neutral-500 mt-1">Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        <div class="flex justify-center gap-3 border-t border-neutral-200 p-4 dark:border-neutral-700">
            <button x-on:click="modalIsOpen = false"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-200 dark:hover:bg-neutral-600">
                Cancel
            </button>
            <button x-on:click="$wire.deletePengeluaran(selectedId); modalIsOpen = false"
                class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600">
                Delete
            </button>
        </div>
    </x-mdl>

</div>
