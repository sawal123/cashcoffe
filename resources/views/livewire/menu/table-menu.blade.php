<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Menu' }}</h6>
        <x-breadcrumb :title="$title ?? 'Menu'" />
    </div>
    <x-toast />

    {{-- Header Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div class="flex flex-wrap items-center gap-2">
            <x-droppage perPage="{{ $perPage }}" />

            <div class="w-full lg:max-w-[300px]  flex-none">
                <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Cari menu..."
                    class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700"
                    prefix='<i class="ri-search-line text-xl leading-none"></i>' />
            </div>

            {{-- Category Filter --}}
            <div class=" flex justify-end">
                <x-ui.select-modern model="category" :options="$categories" :activeValue="$category" placeholder="Semua Kategori" />
            </div>
        </div>

        @hasrole('superadmin')
            <div class="flex justify-end gap-2">
                @if(count($selectedMenuIds) > 0)
                    <button type="button"
                        x-data="{ exporting: false }"
                        data-export-url="{{ $this->selectedMenuExportUrl }}"
                        @click.prevent="
                            if (exporting) return;

                            exporting = true;

                            fetch($el.dataset.exportUrl, {
                                credentials: 'same-origin',
                                headers: { Accept: 'application/pdf' },
                            })
                                .then(async (response) => {
                                    if (! response.ok) {
                                        throw new Error(await response.text() || 'Export PDF gagal.');
                                    }

                                    const blob = await response.blob();
                                    const url = URL.createObjectURL(blob);
                                    const link = document.createElement('a');
                                    const disposition = response.headers.get('content-disposition') || '';
                                    const filename = disposition.match(/filename=&quot;?([^&quot;;]+)&quot;?/i)?.[1] || 'komposisi-menu-terpilih.pdf';

                                    link.href = url;
                                    link.download = filename;
                                    document.body.appendChild(link);
                                    link.click();
                                    link.remove();
                                    URL.revokeObjectURL(url);
                                })
                                .catch(() => {
                                    window.open($el.dataset.exportUrl, '_blank');
                                })
                                .finally(() => {
                                    exporting = false;
                                });
                        "
                        :disabled="exporting"
                        class="inline-flex min-w-[210px] items-center justify-center px-5 py-2.5 bg-red-600 hover:bg-red-700 disabled:cursor-wait disabled:bg-red-500 shadow-red-500/30 text-white text-sm font-bold rounded-2xl shadow-lg transition-all active:scale-95">
                        <i x-show="!exporting" class="ri-file-pdf-2-line mr-2 text-lg leading-none"></i>
                        <i x-show="exporting" x-cloak class="ri-loader-4-line mr-2 text-lg leading-none animate-spin"></i>
                        <span x-text="exporting ? 'Menyiapkan PDF...' : 'Export PDF Terpilih ({{ count($selectedMenuIds) }})'"></span>
                    </button>
                    <button type="button" wire:click="clearSelectedMenus"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 text-neutral-700 dark:text-neutral-200 text-sm font-bold rounded-2xl transition-all hover:bg-neutral-50 dark:hover:bg-neutral-800 active:scale-95">
                        Batal Pilih
                    </button>
                @endif
                <a href="/menu/create" wire:navigate
                    class="inline-flex items-center justify-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 shadow-blue-500/30 text-white text-sm font-bold rounded-2xl shadow-lg transition-all active:scale-95">
                    <i class="ri-add-circle-line mr-2 text-lg leading-none"></i>
                    Tambah Menu
                </a>
            </div>
        @endhasrole
    </div>

    @php
        $currentPageMenuIds = $menu->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
        $selectedIds = collect($selectedMenuIds)->map(fn ($id) => (string) $id)->all();
        $allCurrentPageSelected = count($currentPageMenuIds) > 0 && empty(array_diff($currentPageMenuIds, $selectedIds));
    @endphp

    <x-ui.table :headers="[
        ['name' => '', 'align' => 'center'],
        ['name' => '#', 'align' => 'center'],
        'Menu Item',
        'Kategori',
        'Harga',
        ['name' => 'Terjual', 'align' => 'center'],
        ['name' => 'Status', 'align' => 'center'],
        ['name' => 'Action', 'align' => 'center'],
    ]">
        @forelse ($menu as $item)
            <tr wire:key="menu-row-{{ $item->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition">
                <td data-label="Pilih" class="px-4 sm:px-6 py-4 text-center">
                    <input type="checkbox" wire:model.live="selectedMenuIds" value="{{ $item->id }}"
                        class="h-4 w-4 rounded border-neutral-300 text-red-600 focus:ring-red-500">
                </td>
                <td data-label="#" class="px-4 sm:px-6 py-4 text-center text-sm text-neutral-500">
                    {{ ($menu->currentPage() - 1) * $menu->perPage() + $loop->iteration }}
                </td>
                <td data-label="Item" class="px-4 sm:px-6 py-4">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('storage/' . $item->gambar) }}" alt="{{ $item->nama_menu }}" loading="lazy" decoding="async"
                            class="shrink-0 rounded-2xl w-12 h-12 object-cover bg-neutral-100 dark:bg-neutral-700 border border-neutral-100 dark:border-neutral-700 shadow-sm">
                        <div>
                            <p class="font-bold text-neutral-800 dark:text-neutral-200">{{ $item->nama_menu }}</p>
                            <p class="text-[10px] text-neutral-500 uppercase tracking-widest">
                                {{ $item->kode ?? 'MENU-' . $item->id }}</p>
                        </div>
                    </div>
                </td>
                <td data-label="Kategori" class="px-4 sm:px-6 py-4">
                    <span
                        class="text-sm font-medium text-neutral-600 dark:text-neutral-400 bg-neutral-100 dark:bg-neutral-700 px-2.5 py-1 rounded-lg border border-neutral-200 dark:border-neutral-600">
                        {{ $item->category->nama ?? '-' }}
                    </span>
                </td>
                <td data-label="Harga" class="px-4 sm:px-6 py-4">
                    @php
                        $tieredPrice = $item->menuPrices->first();
                        $hargaDisplay = $tieredPrice
                            ? ($tieredPrice->h_promo > 0
                                ? $tieredPrice->h_promo
                                : $tieredPrice->harga)
                            : ($item->h_promo == 0
                                ? $item->harga
                                : $item->h_promo);
                    @endphp
                    <span class="font-bold text-neutral-900 dark:text-white">Rp
                        {{ number_format($hargaDisplay, 0, ',', '.') }}</span>
                </td>
                <td data-label="Terjual" class="px-4 sm:px-6 py-4 text-center text-sm font-medium text-blue-600 dark:text-blue-400">
                    {{ number_format($item->jumlah_terjual ?? 0, 0, ',', '.') }}
                </td>
                <td data-label="Status" class="px-4 sm:px-6 py-4 text-center">
                    <span
                        class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border {{ $item->is_active ? 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400' }}">
                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td data-label="Aksi" class="px-4 sm:px-6 py-4 text-center">
                    <div class="flex justify-center gap-2">
                        @hasrole('superadmin')
                            <a wire:key="ingredient-{{ $item->id }}"
                                href="{{ route('menu-ingredient.index', ['menu' => $item->id]) }}"
                                wire:navigate title="Lihat Komposisi Menu"
                                class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center hover:bg-amber-600 hover:text-white transition-all">
                                <i class="ri-restaurant-2-line text-sm leading-none"></i>
                            </a>
                            <a wire:key="pdf-{{ $item->id }}"
                                href="{{ route('menu-ingredient.export-pdf', ['menu' => $item->id]) }}"
                                target="_blank" title="Export Komposisi PDF"
                                class="w-8 h-8 rounded-xl bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center hover:bg-red-600 hover:text-white transition-all">
                                <i class="ri-file-pdf-2-line text-sm leading-none"></i>
                            </a>
                            <a wire:key="var-{{ $item->id }}" href="{{ route('menu.variants', $item->id) }}"
                                wire:navigate title="Kelola Varian"
                                class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center hover:bg-purple-600 hover:text-white transition-all">
                                <i class="ri-equalizer-line text-sm leading-none"></i>
                            </a>
                            <x-ui.action-edit wire:key="edit-{{ $item->id }}"
                                href="/menu/{{ base64_encode($item->id) }}/edit" wire:navigate />
                            <x-ui.action-delete wire:key="del-{{ $item->id }}"
                                @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id)) }} })" />
                        @else
                            <span
                                class="text-[10px] text-neutral-400 font-bold uppercase tracking-tighter">Centralized</span>
                        @endhasrole
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center py-12 text-neutral-500">
                    <div class="flex flex-col items-center justify-center gap-3">
                        <i class="ri-inbox-line text-4xl leading-none"></i>
                        <span class="text-sm">Tidak ada menu ditemukan.</span>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    @hasrole('superadmin')
        @if($menu->count() > 0)
            <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-neutral-500 dark:text-neutral-400">
                <button type="button" wire:click="toggleCurrentPageSelection(@js($currentPageMenuIds))"
                    class="inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 py-2 font-bold text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800">
                    <i class="{{ $allCurrentPageSelected ? 'ri-checkbox-multiple-fill' : 'ri-checkbox-multiple-line' }} text-base leading-none"></i>
                    {{ $allCurrentPageSelected ? 'Batalkan Pilihan Halaman Ini' : 'Pilih Semua di Halaman Ini' }}
                </button>
                @if(count($selectedMenuIds) > 0)
                    <span>{{ count($selectedMenuIds) }} menu dipilih untuk export PDF.</span>
                @endif
            </div>
        @endif
    @endhasrole

    <div class="mt-4">
        {{ $menu->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    <x-mdal name="confirm-delete">
        <div class="px-6 py-6 text-center">
            <div
                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-rose-100 text-rose-600 shadow-sm border border-rose-200">
                <i class="ri-alert-line text-2xl leading-none"></i>
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

                <x-ui.button type="button" color="danger"
                    @click="$wire.deletemenu(selectedId); $dispatch('close-modal', { name: 'confirm-delete' })"
                    class="!px-5 !py-2.5">
                    Ya, Hapus
                </x-ui.button>
            </div>
        </div>
    </x-mdal>
</div>
