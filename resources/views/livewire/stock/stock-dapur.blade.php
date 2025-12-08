<div>

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
                </tr>
            </thead>

            <tbody>
                @forelse($items as $i)
                <tr>
                    <td class="border-r border-neutral-200 dark:border-neutral-600">{{ $i->nama_bahan }}</td>
                    <td class="border-r border-neutral-200 dark:border-neutral-600">{{ number_format($i->stok,0,',','.') }}</td>
                    <td class="border-r border-neutral-200 dark:border-neutral-600">{{ number_format($i->stok,0,',','.') }}</td>
                    <td class="border-r border-neutral-200 dark:border-neutral-600">{{ $i->satuan->nama_satuan }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center py-3 text-neutral-500">
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

</div>
