<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" wire:navigate class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-400 hover:text-primary-600 transition-all shadow-sm">
                <iconify-icon icon="lucide:arrow-left" class="text-xl"></iconify-icon>
            </a>
            <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Menu' }}</h6>
        </div>
        <x-breadcrumb :title="$title ?? 'Menu'" />
    </div>
    <x-toast />
    @php
        // $categoryId = $categoryId ?? null;
        $submit = $menuId ? 'update(' . $menuId . ')' : 'simpan';
        $button = $menuId ? 'Update' : 'Simpan';
    @endphp
    <form wire:submit.prevent="{{ $submit }}" class="grid grid-cols-12 gap-4" enctype="multipart/form-data">
        {{-- Nama Menu --}}
        <div class="md:col-span-12 col-span-12">
            <x-ui.input 
                label="Nama Menu" 
                wire:model="nama_menu" 
                placeholder="Masukkan nama menu" 
                required 
            />
        </div>

        {{-- Kategori --}}
        <div class="md:col-span-6 col-span-12">
            <x-ui.select label="Kategori" wire:model="categories_id" required>
                <option value="">-- Pilih Kategori --</option>
                @foreach ($category as $item)
                    <option value="{{ $item->id }}">{{ $item->nama }}</option>
                @endforeach
            </x-ui.select>
        </div>

        {{-- Status Aktif --}}
        <div class="md:col-span-6 col-span-12">
            <label class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-2 block">Status Menu</label>
            <label class="flex items-center h-[50px] cursor-pointer bg-neutral-50 dark:bg-neutral-900 px-4 rounded-2xl">
                <input type="checkbox" class="sr-only peer" wire:model="is_active">
                <span
                    class="relative w-11 h-6 bg-gray-400 peer-focus:outline-none rounded-full peer dark:bg-gray-500 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></span>
                <span class="line-height-1 font-bold ms-3 {{ $is_active ? 'text-blue-600' : 'text-gray-500' }} text-sm uppercase">
                    {{ $is_active ? 'Aktif' : 'Non-Aktif' }}
                </span>
            </label>
        </div>

        {{-- Harga Pokok (Global) --}}
        <div class="md:col-span-12 col-span-12">
            <x-ui.input 
                label="Harga Pokok (Produksi)" 
                type="number" 
                step="1" 
                wire:model.lazy="h_pokok" 
                prefix="Rp" 
                placeholder="0" 
                required 
            />
        </div>

        {{-- Section: Harga Per Tier --}}
        <div class="col-span-12 mt-4">
            <h4 class="text-md font-bold text-neutral-800 dark:text-neutral-200 mb-4 border-b pb-2 flex items-center gap-2">
                <iconify-icon icon="solar:tag-price-bold-duotone" class="text-xl text-blue-500"></iconify-icon>
                Pengaturan Harga Jual (Berdasarkan Tier)
            </h4>
            <div class="grid grid-cols-12 gap-6">
                @foreach ($tiers as $tier)
                    <div class="md:col-span-12 lg:col-span-6 col-span-12 p-4 bg-neutral-100 dark:bg-neutral-800/50 rounded-2xl border border-neutral-200 dark:border-neutral-700">
                        <div class="flex items-center justify-between mb-3">
                            <span class="font-bold text-neutral-700 dark:text-neutral-300">{{ $tier->nama_tier }}</span>
                            <span class="text-[10px] px-2 py-1 bg-blue-100 text-blue-600 rounded-lg font-bold">POS ACTIVE</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <x-ui.input 
                                label="Harga Jual" 
                                type="number" 
                                step="1"
                                wire:model.lazy="tieredPrices.tier_{{ $tier->id }}.harga" 
                                prefix="Rp" 
                                placeholder="0" 
                                required 
                            />
                            <x-ui.input 
                                label="Harga Promo" 
                                type="number" 
                                step="1"
                                wire:model.lazy="tieredPrices.tier_{{ $tier->id }}.h_promo" 
                                prefix="Rp" 
                                placeholder="0" 
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Deskripsi --}}
        <div class="col-span-12">
            <label class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-2 block">Deskripsi</label>
            <textarea wire:model="deskripsi" 
                class="w-full bg-neutral-50 dark:bg-neutral-900 border-0 rounded-2xl px-4 py-3 placeholder:text-neutral-400 focus:ring-2 focus:ring-blue-500" 
                rows="3" placeholder="Tuliskan deskripsi menu..."></textarea>
        </div>

        {{-- Gambar --}}
        <div class="col-span-12">
            <label class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-2 block">Gambar Menu</label>
            <div class="flex items-center gap-4">
                {{-- Preview Area --}}
                <div class="relative w-32 h-32 bg-neutral-50 dark:bg-neutral-900 rounded-2xl border-2 border-dashed border-neutral-200 dark:border-neutral-700 overflow-hidden flex items-center justify-center group">
                    @if($gambarUrl)
                        <img src="{{ $gambarUrl }}" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                             <iconify-icon icon="lucide:refresh-cw" class="text-white text-xl"></iconify-icon>
                        </div>
                    @else
                        <iconify-icon icon="solar:camera-outline" class="text-3xl text-neutral-300"></iconify-icon>
                    @endif
                    <input type="file" wire:model="gambar" class="absolute inset-0 opacity-0 cursor-pointer">
                </div>
                
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-bold text-neutral-700 dark:text-neutral-300">Upload Photo</span>
                    <span class="text-xs text-neutral-400 italic">Max size: 1MB (JPG, PNG)</span>
                    <div wire:loading wire:target="gambar" class="text-xs text-blue-500 font-bold mt-1">
                        Uploading...
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="col-span-12 pt-4">
            <x-ui.button type="submit" class="w-full">
                {{ $button }} Menu Sekarang
            </x-ui.button>
        </div>
    </form>

</div>