<div>
    <x-toast />

    {{-- Controls Section --}}
    <div class="flex justify-end mb-4">
        <a href="{{ route('riwayat-stock.create') }}" wire:navigate>
            <x-ui.button color="blue" class="!px-5 !py-3 !rounded-2xl !text-sm ">
                <iconify-icon icon="lucide:plus"></iconify-icon> Tambah Stock
            </x-ui.button>
        </a>
    </div>
    <div
        class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-3xl p-4 mb-8 shadow-sm">

        {{-- Row 1: Search, Date, Button --}}
        <div class="flex flex-col gap-3 mb-4">


            {{-- Row untuk Date + Button on Desktop --}}
            <div class="flex flex-col lg:flex-col-2  gap-3 ">
                <div class="w-full">
                    <x-ui.input wire:model.live="search" placeholder="Search by code or item...">
                        <x-slot name="prefix">
                            <iconify-icon icon="lucide:search" class="text-lg"></iconify-icon>
                        </x-slot>
                    </x-ui.input>
                </div>
                {{-- Date: Full width on mobile, fixed on desktop --}}
                <div class="w-full">
                    <x-ui.input type="date" wire:model.live="tanggal" class="!py-3" />
                </div>

                {{-- Button: Full width on mobile, auto on desktop --}}

            </div>
        </div>

        {{-- Row 2: Filter Tabs --}}
        <div class="flex p-1.5 bg-neutral-100 dark:bg-neutral-900 rounded-2xl w-full gap-1 overflow-x-auto">
            <button wire:click="setFilter('semua')"
                class="flex-1 min-w-max lg:flex-1 px-4 py-2.5 rounded-xl text-sm font-bold transition-all {{ $filterType === 'semua' ? 'bg-white dark:bg-neutral-800 shadow-sm text-blue-600' : 'text-neutral-500 hover:text-neutral-700' }}">
                All
            </button>
            <button wire:click="setFilter('in')"
                class="flex-1 min-w-max lg:flex-1 px-4 py-2.5 rounded-xl text-sm font-bold transition-all {{ $filterType === 'in' ? 'bg-white dark:bg-neutral-800 shadow-sm text-blue-600' : 'text-neutral-500 hover:text-neutral-700' }}">
                Masuk
            </button>
            <button wire:click="setFilter('out')"
                class="flex-1 min-w-max lg:flex-1 px-4 py-2.5 rounded-xl text-sm font-bold transition-all {{ $filterType === 'out' ? 'bg-white dark:bg-neutral-800 shadow-sm text-blue-600' : 'text-neutral-500 hover:text-neutral-700' }}">
                Keluar
            </button>
        </div>

    </div>

    <x-ui.table :headers="[
        '#',
        'Code',
        'Item Name',
        'Type',
        ['name' => 'Qty', 'align' => 'center'],
        ['name' => 'Before', 'align' => 'center'],
        ['name' => 'After', 'align' => 'center'],
        'Description',
        'Date',
        ['name' => 'Action', 'align' => 'center'],
    ]">
        @forelse ($riwayats as $item)
            <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-700/50 transition-colors">
                <td class="px-6 py-5 text-sm font-bold text-neutral-400">
                    {{ ($riwayats->currentPage() - 1) * $riwayats->perPage() + $loop->iteration }}
                </td>
                <td class="px-6 py-5 text-sm font-extrabold text-blue-600">
                    {{ $item->kode }}
                </td>
                <td class="px-6 py-5 text-sm font-bold text-neutral-800 dark:text-neutral-200">
                    {{ $item->ingredient->nama_bahan }}
                </td>
                <td class="px-6 py-5 text-sm">
                    @if ($item->tipe === 'in')
                        <span
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-black bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                            <iconify-icon icon="lucide:arrow-down-left" class="text-sm"></iconify-icon>
                            MASUK
                        </span>
                    @else
                        <span
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-black bg-orange-50 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400">
                            <iconify-icon icon="lucide:arrow-up-right" class="text-sm"></iconify-icon>
                            KELUAR
                        </span>
                    @endif
                </td>
                <td class="px-6 py-5 text-sm font-black text-center">
                    @if ($item->tipe === 'in')
                        <span
                            class="text-blue-600 dark:text-blue-400">+{{ number_format($item->qty, 0, ',', '.') }}</span>
                    @else
                        <span
                            class="text-orange-600 dark:text-orange-400">-{{ number_format($item->qty, 0, ',', '.') }}</span>
                    @endif
                </td>
                <td class="px-6 py-5 text-sm font-bold text-neutral-500 dark:text-neutral-400 text-center">
                    {{ number_format($item->qty_before, 0, ',', '.') }}
                </td>
                <td class="px-6 py-5 text-sm font-bold text-neutral-500 dark:text-neutral-400 text-center">
                    {{ number_format($item->qty_after, 0, ',', '.') }}
                </td>
                <td class="px-6 py-5 text-sm font-medium text-neutral-500 dark:text-neutral-400 max-w-[200px] truncate">
                    {{ $item->keterangan ?? '-' }}
                </td>
                <td class="px-6 py-5 text-sm font-bold text-neutral-500 dark:text-neutral-400">
                    {{ $item->created_at->format('M d, Y') }}
                </td>
                <td class="px-6 py-5 text-sm text-center">
                    <div class="flex items-center justify-center gap-3">
                        <x-ui.action-edit href="/riwayat-stock/{{ base64_encode($item->id) }}/edit" wire:navigate />
                        <x-ui.action-delete
                            @click="$dispatch('open-modal', { name: 'confirm-delete', id: '{{ base64_encode($item->id) }}' })" />
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center justify-center gap-3">
                        <iconify-icon icon="lucide:folder-open" class="text-5xl text-neutral-200"></iconify-icon>
                        <p class="text-neutral-400 font-bold">No inventory movements found.</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>
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
