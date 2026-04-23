<div class="flex flex-col-3 md:flex-row items-start gap-4 lg:gap-6 w-full">
    <x-toast />

    {{-- Kiri: Produk --}}
    <div class="flex-1 min-w-0" id="menu">
        {{-- Search & Category Filter --}}
        <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-6 relative z-10">
            <div class="shrink-0">
                <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Cari menu favorit..."
                    class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700"
                    prefix='<iconify-icon icon="lucide:search" class="text-xl"></iconify-icon>' />
            </div>

            <div class="flex justify-end">
                <x-ui.select-modern model="selectedCategoryId" :options="$categories" :activeValue="$selectedCategoryId"
                    placeholder="Semua Menu" />
            </div>
        </div>

        @php
            $filteredCategories = $selectedCategoryId ? $categories->where('id', $selectedCategoryId) : $categories;
        @endphp

        <div class="space-y-8">
            @foreach ($filteredCategories as $category)
                @if ($category->menus->count() > 0)
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
                            <h2 class="text-xl font-black text-neutral-800 dark:text-white tracking-tight">
                                {{ $category->nama }}
                            </h2>
                            <span
                                class="text-xs font-bold text-neutral-400 bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 rounded-lg">{{ $category->menus->count() }}
                                Items</span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3">
                            @foreach ($category->menus as $item)
                                @php
                                    $tieredPrice = $item->menuPrices->first();
                                    $harga = $tieredPrice 
                                        ? ($tieredPrice->h_promo > 0 ? $tieredPrice->h_promo : $tieredPrice->harga)
                                        : ($item->h_promo == 0 ? $item->harga : $item->h_promo);
                                    $hasVariants = $item->variantGroups && $item->variantGroups->count() > 0;
                                    $isPromo = $tieredPrice ? ($tieredPrice->h_promo > 0) : ($item->h_promo > 0);
                                @endphp
                                <article wire:click="addPesanan({{ $item->id }})"
                                    class="group relative flex flex-col rounded-2xl border border-neutral-100 bg-white shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer overflow-hidden dark:border-neutral-700 dark:bg-neutral-800">

                                    <div class="aspect-[16/11] overflow-hidden relative">
                                        <img src="{{ asset('storage/' . $item->gambar) }}"
                                            class="object-cover w-full h-full transition duration-500 group-hover:scale-110"
                                            alt="{{ $item->nama_menu }}" />

                                        <div
                                            class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                                        </div>

                                        @if ($hasVariants)
                                            <div
                                                class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm shadow-sm text-purple-600 text-[10px] px-3 py-1.5 rounded-full font-black uppercase tracking-widest border border-purple-100">
                                                Varian
                                            </div>
                                        @endif

                                        @if ($isPromo)
                                            <div
                                                class="absolute top-3 left-3 bg-red-600 text-white text-[10px] px-3 py-1.5 rounded-full font-black uppercase tracking-widest shadow-lg shadow-red-500/30">
                                                Promo
                                            </div>
                                        @endif
                                    </div>

                                    <div class="p-3 flex-1 flex flex-col justify-between">
                                        <div>
                                            <h3
                                                class="text-xs font-bold text-neutral-800 dark:text-neutral-100 mb-1 leading-snug group-hover:text-blue-600 transition-colors line-clamp-2">
                                                {{ $item->nama_menu }}
                                            </h3>
                                        </div>
                                        <div class="flex items-center justify-between mt-2">
                                            <p class="text-sm font-black text-blue-700 dark:text-blue-400">
                                                Rp {{ number_format($harga, 0, ',', '.') }}
                                            </p>
                                            <div
                                                class="w-7 h-7 rounded-lg bg-neutral-50 dark:bg-neutral-700 flex items-center justify-center text-neutral-400 group-hover:bg-blue-600 group-hover:text-white transition-all shadow-sm">
                                                <iconify-icon icon="mingcute:add-line" class="text-base"></iconify-icon>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>


    {{-- Kanan: Pesanan --}}
    @include('livewire.order.pesanan-item')

    {{-- MODAL VARIAN --}}
    <x-mdal name="variant-modal">
        @if ($selectedMenuForVariant)
            <div class="p-6">
                {{-- Header Detail --}}
                <div class="flex items-center gap-5 mb-8 pb-6 border-b border-neutral-100 dark:border-neutral-700">
                    <img src="{{ asset('storage/' . $selectedMenuForVariant['gambar']) }}"
                        class="w-20 h-20 rounded-[1.5rem] object-cover shadow-lg border-2 border-white dark:border-neutral-800">
                    <div>
                        <h3 class="font-black text-xl text-neutral-900 dark:text-white mb-1">
                            {{ $selectedMenuForVariant['nama_menu'] }}
                        </h3>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-neutral-400 uppercase tracking-widest">Base
                                Price:</span>
                            <p class="text-lg font-black text-blue-600 dark:text-blue-400">
                                Rp {{ number_format($selectedMenuForVariant['harga_base'], 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Info Wajib Dipilih --}}
                @php
                    $hasRequiredGroups = collect($selectedMenuForVariant['groups'])->some(fn($g) => $g->is_required);
                @endphp
                @if ($hasRequiredGroups)
                    <div
                        class="mb-6 p-4 bg-amber-50 border border-amber-100 rounded-2xl dark:bg-amber-900/20 dark:border-amber-900/50 flex gap-3 items-center">
                        <iconify-icon icon="mingcute:warning-line" class="text-amber-500 text-xl"></iconify-icon>
                        <p class="text-xs font-bold text-amber-700 dark:text-amber-300">
                            Pilih opsi wajib yang bertanda bintang (*)
                        </p>
                    </div>
                @endif

                <div class="space-y-8">
                    @foreach ($selectedMenuForVariant['groups'] as $group)
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <label
                                    class="text-[10px] font-black text-neutral-400 uppercase tracking-widest flex items-center gap-2">
                                    {{ $group->nama_group }}
                                    @if ($group->is_required)
                                        <span class="text-red-500 font-black text-lg -mt-1">*</span>
                                    @endif
                                </label>
                                @if ($group->selection_type == 'multiple')
                                    <span
                                        class="text-[10px] font-bold text-blue-500 bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded-lg border border-blue-100 dark:border-blue-800">Bisa
                                        pilih banyak</span>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                @foreach ($group->options as $option)
                                            @php
                                                $isSelected =
                                                    isset($tempSelectedOptions[$group->id]) &&
                                                    in_array($option->id, $tempSelectedOptions[$group->id]);
                                            @endphp
                                            <button type="button"
                                                wire:click="selectOption({{ $group->id }}, {{ $option->id }}, '{{ $group->selection_type }}')"
                                                class="relative flex flex-col p-4 rounded-[1.5rem] border-2 transition-all text-left overflow-hidden group
                                                                                                                                                                                                                                                    {{ $isSelected
                                    ? 'bg-blue-600 border-blue-600 shadow-lg shadow-blue-500/20 text-white'
                                    : 'bg-white border-neutral-100 hover:border-blue-300 dark:bg-neutral-800 dark:border-neutral-700 text-neutral-700 dark:text-neutral-300' }}">

                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-sm font-black leading-tight">
                                                        {{ $option->nama_opsi }}
                                                    </span>
                                                    @if ($isSelected)
                                                        <iconify-icon icon="mingcute:check-circle-fill"
                                                            class="text-xl text-white"></iconify-icon>
                                                    @endif
                                                </div>

                                                @if ($option->extra_price > 0)
                                                    <span
                                                        class="text-[11px] font-bold {{ $isSelected ? 'text-blue-100' : 'text-blue-600 dark:text-blue-400' }}">
                                                        +Rp {{ number_format($option->extra_price, 0, ',', '.') }}
                                                    </span>
                                                @else
                                                    <span
                                                        class="text-[11px] font-medium {{ $isSelected ? 'text-blue-100' : 'text-neutral-400' }}">Gratis</span>
                                                @endif
                                            </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Footer Action --}}
                <div class="mt-10 pt-8 border-t border-neutral-100 dark:border-neutral-700">
                    <div class="flex items-center justify-between mb-6">
                        <span class="text-xs font-black text-neutral-400 uppercase tracking-widest">Estimasi
                            Total:</span>
                        <span class="text-2xl font-black text-neutral-900 dark:text-white">
                            Rp
                            {{ number_format($selectedMenuForVariant['harga_base'] + $totalExtraPrice, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex gap-4">
                        <button type="button" x-on:click="$dispatch('close-modal', { name: 'variant-modal' })"
                            class="flex-1 px-6 py-4 rounded-2xl border border-neutral-200 dark:border-neutral-700 text-sm font-bold text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-700 transition">
                            Batal
                        </button>
                        <x-ui.button type="button" wire:click="confirmVariant" color="blue" class="flex-[2] !py-4 shadow-xl"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove>Tambahkan ke Pesanan</span>
                            <span wire:loading.flex class="items-center justify-center gap-2">
                                <iconify-icon icon="mingcute:loading-fill" class="animate-spin text-xl"></iconify-icon>
                                <span>Memproses...</span>
                            </span>
                        </x-ui.button>
                    </div>
                </div>
            </div>
        @endif
    </x-mdal>
</div>