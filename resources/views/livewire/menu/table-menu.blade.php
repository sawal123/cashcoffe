<div>
    <x-toast />
    
    {{-- Header Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div class="flex flex-wrap items-center gap-2">
            <x-droppage perPage="{{ $perPage }}" />
            
            <div class="sm:w-[250px]">
                <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Cari menu..." class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
            </div>

            {{-- Category Filter --}}
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                    class="h-[46px] px-5 flex items-center gap-2 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-2xl text-sm font-bold text-neutral-700 dark:text-neutral-300 hover:border-blue-500 transition-all active:scale-95 shadow-sm">
                    <iconify-icon icon="mingcute:filter-line" class="text-lg text-neutral-400"></iconify-icon>
                    <span>{{ $category ? $categories->where('id', $category)->first()->nama : 'Semua Kategori' }}</span>
                    <iconify-icon icon="mingcute:down-line" class="text-neutral-400 transition-transform" :class="open ? 'rotate-180' : ''"></iconify-icon>
                </button>

                <div x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    class="z-50 bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-2xl shadow-xl w-56 absolute mt-2 left-0 overflow-hidden">
                    <div class="p-1">
                        <button wire:click="$set('category', '')" @click="open = false"
                            class="w-full text-left px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors {{ !$category ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30' : 'text-neutral-600 dark:text-neutral-400' }}">
                            Semua Kategori
                        </button>
                        <div class="my-1 border-t border-neutral-100 dark:border-neutral-700"></div>
                        @foreach ($categories as $cat)
                            <button wire:click="$set('category', {{ $cat->id }})" @click="open = false"
                                class="w-full text-left px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors {{ $category == $cat->id ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30' : 'text-neutral-600 dark:text-neutral-400' }}">
                                {{ $cat->nama }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        @hasrole('superadmin')
        <div class="flex gap-2">
            <x-ui.button-link href="/menu/create" icon="mingcute:add-circle-line">
                Tambah Menu
            </x-ui.button-link>
        </div>
        @endhasrole
    </div>

    <x-ui.table :headers="[
        ['name' => '#', 'align' => 'center'],
        'Menu Item',
        'Kategori',
        'Harga',
        ['name' => 'Terjual', 'align' => 'center'],
        ['name' => 'Status', 'align' => 'center'],
        ['name' => 'Action', 'align' => 'center']
    ]">
        @forelse ($menu as $item)
            <tr wire:key="{{ $item->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition">
                <td class="px-6 py-4 text-center text-sm text-neutral-500">
                    {{ ($menu->currentPage() - 1) * $menu->perPage() + $loop->iteration }}
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('storage/' . $item->gambar) }}" alt="{{ $item->nama_menu }}"
                            class="shrink-0 rounded-2xl w-12 h-12 object-cover border border-neutral-100 dark:border-neutral-700 shadow-sm">
                        <div>
                            <p class="font-bold text-neutral-800 dark:text-neutral-200">{{ $item->nama_menu }}</p>
                            <p class="text-[10px] text-neutral-500 uppercase tracking-widest">{{ $item->kode ?? 'MENU-'.$item->id }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400 bg-neutral-100 dark:bg-neutral-700 px-2.5 py-1 rounded-lg border border-neutral-200 dark:border-neutral-600">
                        {{ $item->category->nama ?? '-' }}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <span class="font-bold text-neutral-900 dark:text-white">Rp {{ number_format($item->harga, 0, ',', '.') }}</span>
                </td>
                <td class="px-6 py-4 text-center text-sm font-medium text-blue-600 dark:text-blue-400">
                    {{ number_format($item->jumlah_terjual ?? 0, 0, ',', '.') }}
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border {{ $item->is_active ? 'bg-green-100 text-green-700 border-green-200' : 'bg-red-100 text-red-700 border-red-200' }}">
                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex justify-center gap-2">
                        @hasrole('superadmin')
                            <a href="{{ route('menu.variants', $item->id) }}" wire:navigate
                                title="Kelola Varian"
                                class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center hover:bg-purple-600 hover:text-white transition-all">
                                <iconify-icon icon="solar:tuning-square-2-linear" class="text-lg"></iconify-icon>
                            </a>
                            <x-ui.action-edit href="/menu/{{ base64_encode($item->id) }}/edit" wire:navigate />
                            <x-ui.action-delete @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id)) }} })" />
                        @else
                            <span class="text-[10px] text-neutral-400 font-bold uppercase tracking-tighter">Centralized</span>
                        @endhasrole
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-12 text-neutral-500">
                    <div class="flex flex-col items-center justify-center gap-3">
                        <iconify-icon icon="mingcute:ghost-line" class="text-4xl"></iconify-icon>
                        <span class="text-sm">Tidak ada menu ditemukan.</span>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-4">
        {{ $menu->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    <x-mdal name="confirm-delete">
        <div class="px-6 py-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-rose-100 text-rose-600 shadow-sm border border-rose-200">
                <iconify-icon icon="lucide:alert-triangle" class="text-2xl"></iconify-icon>
            </div>

            <h3 class="mb-1 text-lg font-bold text-neutral-900 dark:text-neutral-100">Hapus Menu Ini?</h3>
            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Tindakan ini akan menghapus menu secara permanen dari database.
            </p>

            <div class="flex justify-center gap-3 border-t pt-6 border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'confirm-delete' })"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                    Batal
                </button>

                <x-ui.button type="button" color="danger" @click="$wire.deletemenu(selectedId); $dispatch('close-modal', { name: 'confirm-delete' })" class="!px-5 !py-2.5">
                    Ya, Hapus
                </x-ui.button>
            </div>
        </div>
    </x-mdal>
</div>
