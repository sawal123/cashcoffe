<div class="sm:flex  justify-between lg:flex-row gap-4">
    <x-toast />
    {{-- Kiri: Produk --}}

    <div class="flex-1 min-w-0 order-1 lg:order-1" id="menu">
        <div class="sm:w-[300px] w-ful mb-2">
            <div class="flex gap-2">
                <x-droppage perPage="{{ $perPage }}" />
                <div class="sm:w-[300px] w-ful">
                    <x-input wire:model.live="search" place="Cari..." />
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 mb-4">
            <button wire:click="$set('selectedCategoryId', null)"
                class="px-3 py-1 rounded-full border border-slate-300 text-sm bg-white text-slate-800">
                Semua
            </button>
            @foreach ($categories as $category)
                <button wire:click="filterByCategory({{ $category->id }})"
                    class="px-3 py-1 rounded-full border border-slate-300 text-sm
                           {{ $selectedCategoryId === $category->id ? 'bg-slate-800 text-white' : 'bg-white text-slate-800' }}">
                    {{ $category->nama }}
                </button>
            @endforeach
        </div>


        @php
$filteredCategories = $selectedCategoryId
    ? $categories->where('id', $selectedCategoryId)
    : $categories;
        @endphp

        @foreach ($filteredCategories as $category)
            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-2">{{ $category->nama }}</h2>

            <div class="grid w-full grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
                @foreach ($category->menus as $item)
                    @php
        $harga = $item->h_promo == 0 ? $item->harga : $item->h_promo;
        $hasVariants = $item->variantGroups && $item->variantGroups->count() > 0;
                    @endphp
                    <article wire:click="addPesanan({{ $item->id }})"
                        class="hover:shadow-xl cursor-pointer group flex flex-col rounded-xl overflow-hidden border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800 relative">
                        <div class="h-40 md:h-52 overflow-hidden">
                            <img src="{{ asset('storage/' . $item->gambar) }}"
                                class="object-cover w-full h-full transition duration-500 ease-out group-hover:scale-105"
                                alt="Produk" />
                        </div>
                        @if($hasVariants)
                            <div
                                class="absolute top-2 right-2 bg-purple-500 text-white text-[10px] px-2 py-1 rounded-full font-bold">
                                Ada Varian
                            </div>
                        @endif
                        <div class="p-3">
                            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                {{ $item->nama_menu }}
                            </p>
                            <p class="text-base font-bold text-slate-900 dark:text-white">
                                Rp{{ number_format($harga, 0, ',', '.') }}
                            </p>
                        </div>
                    </article>
                @endforeach
            </div>
        @endforeach
        {{-- {{ $menus->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }} --}}
    </div>


    {{-- Kanan: Pesanan --}}
    @include('livewire.order.pesanan-item')

    {{-- MODAL VARIAN --}}
    <x-mdal name="variant-modal">
        @if ($selectedMenuForVariant)
            <div class="p-6">
                {{-- Header Detail --}}
                <div class="flex items-center gap-4 mb-6 pb-6 border-b border-slate-100 dark:border-slate-700">
                    <img src="{{ asset('storage/' . $selectedMenuForVariant['gambar']) }}"
                        class="w-16 h-16 rounded-xl object-cover shadow-sm">
                    <div>
                        <h3 class="font-bold text-lg text-slate-800 dark:text-white">
                            {{ $selectedMenuForVariant['nama_menu'] }}</h3>
                        <p class="text-sm text-blue-600 font-bold">Rp
                            {{ number_format($selectedMenuForVariant['harga_base'], 0, ',', '.') }}</p>
                    </div>
                </div>

                {{-- Info Wajib Dipilih --}}
                @php
                    $hasRequiredGroups = collect($selectedMenuForVariant['groups'])->some(fn($g) => $g->is_required);
                @endphp
                @if ($hasRequiredGroups)
                    <div
                        class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg dark:bg-amber-900/30 dark:border-amber-900">
                        <p class="text-xs text-amber-700 dark:text-amber-200"><span class="font-bold">*</span> Kolom bertanda
                            bintang harus diisi</p>
                    </div>
                @endif

                <div class="space-y-6 px-1">
                    @foreach ($selectedMenuForVariant['groups'] as $group)
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    {{ $group->nama_group }}
                                    @if ($group->is_required)
                                        <span class="text-red-500 ml-1">*</span>
                                    @endif
                                </label>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                @foreach ($group->options as $option)
                                    @php
                                        $isSelected = isset($tempSelectedOptions[$group->id]) && in_array($option->id, $tempSelectedOptions[$group->id]);
                                    @endphp
                                    <button type="button"
                                        wire:click="selectOption({{ $group->id }}, {{ $option->id }}, '{{ $group->selection_type }}')"
                                        class="flex flex-col p-3 rounded-xl border transition-all text-left group
                                                                        {{ $isSelected
                                                                            ? 'bg-blue-50 border-blue-500 ring-1 ring-blue-500 dark:bg-blue-900/30'
                                                                            : 'bg-white border-slate-200 hover:border-blue-300 dark:bg-slate-800 dark:border-slate-700' }}">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span
                                                class="text-sm font-bold {{ $isSelected ? 'text-blue-700 dark:text-blue-400' : 'text-slate-700 dark:text-slate-200' }}">
                                                {{ $option->nama_opsi }}
                                            </span>
                                            @if ($isSelected)
                                                <iconify-icon icon="solar:check-circle-bold"
                                                    class="text-blue-500"></iconify-icon>
                                            @endif
                                        </div>
                                        @if ($option->extra_price > 0)
                                            <span class="text-[11px] font-medium text-blue-600 dark:text-blue-400">
                                                +Rp {{ number_format($option->extra_price, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-[11px] text-slate-400">Tanpa Tambahan</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Footer Action --}}
                <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-4 px-1">
                        <span class="text-sm font-medium text-slate-500">Estimasi Harga:</span>
                        <span class="text-xl font-black text-slate-800 dark:text-white">
                            Rp
                            {{ number_format($selectedMenuForVariant['harga_base'] + $totalExtraPrice, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" x-on:click="$dispatch('close-modal', { name: 'variant-modal' })"
                            class="flex-1 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                            Batal
                        </button>
                        <button type="button" wire:click="confirmVariant"
                            class="flex-[2] flex items-center justify-center px-4 py-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold shadow-lg shadow-orange-500/30 transition"
                            wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed">
                            <span wire:loading.remove>Tambahkan ke pesanan</span>
                            <span wire:loading.flex class="items-center gap-2">
                                <iconify-icon icon="eos-icons:loading" class="inline-block"></iconify-icon>
                                <span>Menambahkan...</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </x-mdal>
</div>