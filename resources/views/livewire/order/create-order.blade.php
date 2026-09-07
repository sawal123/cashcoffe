<div class="order-create-page flex flex-col gap-6 w-full">
    <style>
        .order-create-page {
            width: 100%;
            min-width: 0;
        }

        /* Mobile/tablet only: ancestor .dashboard-main is a column flex with flex-wrap that sizes the
           order layout to its max-content (~1312px from chip rows / product cards), so document
           overflowed the viewport. Diagnosed live: min-width:0 + width:100% on the flex ancestor
           collapses it to the viewport and lets chips scroll locally. This <style> only renders on
           /order/create, so no other page is affected; desktop (>= 1024px) is untouched. */
        @media (max-width: 1023.98px) {
            .dashboard-main-body {
                min-width: 0;
                width: 100%;
            }
        }

        /* ===== STRUCTURAL RESPONSIVE LAYOUT (scoped) ===== */
        .order-create-page .order-create-layout {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            width: 100%;
            min-width: 0;
            align-items: stretch;
        }

        .order-create-page .order-menu-pane {
            width: 100%;
            min-width: 0;
            flex: 0 0 auto;
        }

        .order-create-page .order-cart-pane {
            width: 100%;
            min-width: 0;
            flex: 0 0 auto;
        }

        /* Mobile-only / desktop-only visibility */
        .order-create-page .order-desktop-only {
            display: none;
        }

        /* Local horizontal scroll containers */
        .order-create-page .order-channel-scroll,
        .order-create-page .order-category-scroll {
            max-width: 100%;
            overflow-x: auto;
        }

        /* Search row: single column on mobile */
        .order-create-page .order-search-row {
            display: flex;
            flex-direction: column;
        }

        .order-create-page .order-search-input {
            width: 100%;
            min-width: 0;
        }

        /* Product grid: 2 columns on small mobile */
        .order-create-page .order-product-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            width: 100%;
            min-width: 0;
        }

        /* Bottom spacing so last product not hidden behind bottom cart */
        .order-create-page .order-menu-pane {
            padding-bottom: 7rem;
        }

        @media (min-width: 640px) {
            .order-create-page .order-product-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1024px) {
            .order-create-page .order-create-layout {
                flex-direction: row;
                align-items: flex-start;
                gap: 1.5rem;
            }

            .order-create-page .order-menu-pane {
                flex: 1 1 0%;
                min-width: 0;
                padding-bottom: 0;
            }

            .order-create-page .order-cart-pane {
                flex: 0 0 380px;
                width: 380px;
                max-width: 380px;
                min-width: 380px;
                position: sticky;
                top: 6rem;
                align-self: flex-start;
                z-index: 20;
            }

            .order-create-page .order-mobile-only {
                display: none;
            }

            .order-create-page .order-desktop-only {
                display: block;
            }

            .order-create-page .order-bottom-cart,
            .order-create-page .order-mobile-overlay {
                display: none !important;
            }

            /* Mobile-only elements inside cart hidden on desktop */
            .order-create-page .order-cart-mobile-header,
            .order-create-page .order-cart-mobile-close {
                display: none !important;
            }

            .order-create-page .order-cart-pane #pesan {
                max-height: calc(100vh - 8rem);
                overflow-y: auto;
            }

            .order-create-page .order-search-row {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }

            .order-create-page .order-search-input {
                flex: 0 0 auto;
                width: auto;
            }

            .order-create-page .order-category-dropdown {
                flex: 0 0 auto;
            }

            .order-create-page .order-product-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .order-create-page .order-product-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (min-width: 1536px) {
            .order-create-page .order-product-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
        }
    </style>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" wire:navigate class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-400 hover:text-primary-600 transition-all shadow-sm">
                <iconify-icon icon="lucide:arrow-left" class="text-xl"></iconify-icon>
            </a>
            <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Order' }}</h6>
        </div>
        <x-breadcrumb :title="$title ?? 'Order'" />
    </div>

    <x-toast />

    <div class="order-create-layout">

    {{-- Kiri: Produk --}}
    <div class="order-menu-pane" id="menu">
        {{-- Sales Channel Selection --}}
        <div class="order-channel-scroll flex gap-2 mb-4 overflow-x-auto overscroll-x-contain pb-2 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @foreach($salesChannels as $channel)
                <button wire:click="$set('sales_channel_id', {{ $channel->id }})" 
                    class="shrink-0 whitespace-nowrap rounded-full px-5 py-2.5 text-sm font-bold transition-all {{ $sales_channel_id == $channel->id ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'border border-neutral-200 bg-white text-neutral-600 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700' }}">
                    {{ $channel->nama_channel }}
                </button>
            @endforeach
        </div>

        {{-- Search & Category Filter --}}
        <div class="order-search-row gap-3 mb-5 relative z-10">
            <div class="order-search-input">
                <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Cari menu favorit..."
                    class="w-full !bg-white border border-neutral-200 dark:!bg-neutral-900 dark:border-neutral-700"
                    prefix='<iconify-icon icon="lucide:search" class="text-xl"></iconify-icon>' />
            </div>

            <div class="order-desktop-only order-category-dropdown">
                <x-ui.select-modern model="selectedCategoryId" :options="$categories" :activeValue="$selectedCategoryId"
                    placeholder="Semua Menu" />
            </div>
        </div>

        <div class="order-mobile-only order-category-scroll mb-5 flex gap-2 overflow-x-auto px-1 pb-2 whitespace-nowrap overscroll-x-contain [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            <button type="button" wire:click="$set('selectedCategoryId', null)"
                class="shrink-0 rounded-full border px-4 py-2 text-sm font-bold transition-all {{ blank($selectedCategoryId) ? 'border-blue-600 bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'border-neutral-200 bg-white text-neutral-600 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700' }}">
                Semua
            </button>
            @foreach ($categories as $categoryOption)
                <button type="button" wire:click="$set('selectedCategoryId', {{ $categoryOption->id }})"
                    class="shrink-0 rounded-full border px-4 py-2 text-sm font-bold transition-all {{ (string) $selectedCategoryId === (string) $categoryOption->id ? 'border-blue-600 bg-blue-600 text-white shadow-md shadow-blue-500/30' : 'border-neutral-200 bg-white text-neutral-600 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-neutral-700' }}">
                    {{ $categoryOption->nama }}
                </button>
            @endforeach
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

                        <div class="order-product-grid gap-3">
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
    <div class="order-cart-pane">
        @include('livewire.order.pesanan-item')
    </div>
</div>

    {{-- MODAL VARIAN --}}
    <x-mdal name="variant-modal">
        @if ($selectedMenuForVariant)
            <div class="p-6" x-data="{
                selectedOptions: {},
                optionPrices: {{ json_encode($selectedMenuForVariant['option_prices']) }},
                totalExtra: 0,
                toggle(groupId, optionId, type) {
                    if (type === 'single') {
                        this.selectedOptions[groupId] = [optionId];
                    } else {
                        if (!this.selectedOptions[groupId]) this.selectedOptions[groupId] = [];
                        const idx = this.selectedOptions[groupId].indexOf(optionId);
                        if (idx > -1) this.selectedOptions[groupId].splice(idx, 1);
                        else this.selectedOptions[groupId].push(optionId);
                    }
                    this.calculate();
                },
                calculate() {
                    let sum = 0;
                    for (let g in this.selectedOptions) {
                        this.selectedOptions[g].forEach(id => {
                            sum += (this.optionPrices[id] || 0);
                        });
                    }
                    this.totalExtra = sum;
                },
                isSelected(groupId, optionId) {
                    return this.selectedOptions[groupId] && this.selectedOptions[groupId].includes(optionId);
                }
            }">
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
                                    <button type="button"
                                        @click="toggle({{ $group->id }}, {{ $option->id }}, '{{ $group->selection_type }}')"
                                        class="relative flex flex-col p-4 rounded-[1.5rem] border-2 transition-all text-left overflow-hidden group"
                                        :class="isSelected({{ $group->id }}, {{ $option->id }})
                                            ? 'bg-blue-600 border-blue-600 shadow-lg shadow-blue-500/20 text-white'
                                            : 'bg-white border-neutral-100 hover:border-blue-300 dark:bg-neutral-800 dark:border-neutral-700 text-neutral-700 dark:text-neutral-300'">

                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-black leading-tight">
                                                {{ $option->nama_opsi }}
                                            </span>
                                            <template x-if="isSelected({{ $group->id }}, {{ $option->id }})">
                                                <iconify-icon icon="mingcute:check-circle-fill"
                                                    class="text-xl text-white"></iconify-icon>
                                            </template>
                                        </div>

                                        @if ($option->extra_price > 0)
                                            <span class="text-[11px] font-bold"
                                                :class="isSelected({{ $group->id }}, {{ $option->id }}) ? 'text-blue-100' : 'text-blue-600 dark:text-blue-400'">
                                                +Rp {{ number_format($option->extra_price, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-[11px] font-medium"
                                                :class="isSelected({{ $group->id }}, {{ $option->id }}) ? 'text-blue-100' : 'text-neutral-400'">Gratis</span>
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
                            <span
                                x-text="new Intl.NumberFormat('id-ID').format({{ $selectedMenuForVariant['harga_base'] }} + totalExtra)"></span>
                        </span>
                    </div>
                    <div class="flex gap-4">
                        <button type="button" x-on:click="$dispatch('close-modal', { name: 'variant-modal' })"
                            class="flex-1 px-6 py-4 rounded-2xl border border-neutral-200 dark:border-neutral-700 text-sm font-bold text-neutral-600 dark:text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-700 transition">
                            Batal
                        </button>
                        <x-ui.button type="button" @click="$wire.confirmVariant(JSON.stringify(selectedOptions))"
                            color="blue" class="flex-[2] !py-4 shadow-xl" wire:loading.attr="disabled">
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

    {{-- MODAL SUCCESS / DETAIL PESANAN --}}
    <x-mdal name="order-success">
        <div class="px-6 pb-6" x-data="{ printing: false }">
            <div class="flex items-start justify-between gap-4 border-b border-neutral-100 pb-5 dark:border-neutral-700">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-neutral-400">Detail Pesanan</span>
                    <h3 class="mt-1 text-xl font-black text-neutral-900 dark:text-neutral-100">
                        #{{ $lastKodePesanan ?: '-' }}
                    </h3>
                    <p class="mt-1 text-xs font-medium text-neutral-400">{{ $lastCreatedAt ?: now()->format('d M Y H:i') }}</p>
                </div>
                @php
                    $lastStatusClass = $lastStatusPesanan === 'selesai'
                        ? 'bg-green-100 text-green-700 border-green-200'
                        : 'bg-amber-100 text-amber-700 border-amber-200';
                @endphp
                <span class="rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest {{ $lastStatusClass }}">
                    {{ $lastStatusPesanan ? ucwords($lastStatusPesanan) : 'Diproses' }}
                </span>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-neutral-50 p-3 dark:bg-neutral-900">
                    <span class="block text-[10px] font-bold uppercase tracking-widest text-neutral-400">Pelanggan</span>
                    <span class="mt-1 block text-sm font-bold text-neutral-800 dark:text-neutral-200">{{ $lastNamaCostumer ?: '-' }}</span>
                </div>
                <div class="rounded-2xl bg-neutral-50 p-3 dark:bg-neutral-900">
                    <span class="block text-[10px] font-bold uppercase tracking-widest text-neutral-400">Tipe Order</span>
                    <span class="mt-1 block text-sm font-bold text-indigo-600">{{ $lastSalesChannelName ?: 'Dine In' }}</span>
                </div>
                <div class="rounded-2xl bg-neutral-50 p-3 dark:bg-neutral-900">
                    <span class="block text-[10px] font-bold uppercase tracking-widest text-neutral-400">Metode</span>
                    <span class="mt-1 block text-sm font-bold text-neutral-800 dark:text-neutral-200">{{ $lastPaymentMethodName ?: '-' }}</span>
                </div>
                <div class="rounded-2xl bg-neutral-50 p-3 dark:bg-neutral-900">
                    <span class="block text-[10px] font-bold uppercase tracking-widest text-neutral-400">Item</span>
                    <span class="mt-1 block text-sm font-bold text-neutral-800 dark:text-neutral-200">{{ count($lastOrderItems) }} menu</span>
                </div>
            </div>

            <div class="mt-5">
                <h4 class="mb-3 flex items-center gap-2 text-xs font-black uppercase tracking-widest text-neutral-400">
                    <iconify-icon icon="mingcute:list-check-line" class="text-base text-blue-600"></iconify-icon>
                    Item Pesanan
                </h4>
                <div class="max-h-[240px] space-y-2 overflow-y-auto pr-1 custom-scrollbar">
                    @forelse ($lastOrderItems as $item)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-neutral-100 bg-neutral-50 p-3 dark:border-neutral-700 dark:bg-neutral-900">
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-1 text-sm font-bold text-neutral-800 dark:text-neutral-200">{{ $item['name'] }}</p>
                                @if (!empty($item['variants']))
                                    <p class="text-[10px] italic text-neutral-400">{{ implode(', ', $item['variants']) }}</p>
                                @endif
                                <p class="mt-0.5 text-xs text-neutral-400">
                                    Rp{{ number_format($item['price'], 0, ',', '.') }} x {{ $item['qty'] }}
                                </p>
                            </div>
                            <span class="text-sm font-black text-neutral-900 dark:text-white">
                                Rp{{ number_format($item['subtotal'], 0, ',', '.') }}
                            </span>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-neutral-200 p-6 text-center text-sm font-bold text-neutral-400">
                            Detail item belum tersedia.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-5 space-y-2 border-t-2 border-dashed border-neutral-100 pt-4 dark:border-neutral-700">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-neutral-400">Subtotal</span>
                    <span class="font-bold text-neutral-700 dark:text-neutral-300">Rp{{ number_format($lastTotalPesanan ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-neutral-400">Diskon</span>
                    <span class="font-bold text-green-600">-Rp{{ number_format($lastDiscountPesanan ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between border-t border-neutral-100 pt-3 dark:border-neutral-700">
                    <span class="text-sm font-black uppercase text-neutral-700 dark:text-neutral-300">Total Akhir</span>
                    <span class="text-xl font-black text-blue-700 dark:text-blue-400">Rp{{ number_format($lastFinalTotalPesanan ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="mt-6 flex gap-3 border-t border-neutral-100 pt-4 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'order-success' })"
                    class="flex-1 rounded-2xl border border-neutral-300 bg-white px-5 py-3 text-sm font-bold text-neutral-700 transition hover:bg-neutral-50 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                    Kembali
                </button>

                <button type="button"
                    @click="
                        if (printing) return;
                        printing = true;

                        const printWindow = window.open('', '_blank');

                        $wire.completeLastOrderAndPrint()
                            .then((url) => {
                                if (url) {
                                    if (printWindow) {
                                        printWindow.location.href = url;
                                    } else {
                                        window.location.href = url;
                                    }
                                } else if (printWindow) {
                                    printWindow.close();
                                }
                            })
                            .finally(() => {
                                printing = false;
                            });
                    "
                    :disabled="printing || !{{ $lastPesananId ? 'true' : 'false' }}"
                    class="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-500/20 transition hover:bg-blue-700 disabled:cursor-wait disabled:opacity-70">
                    <iconify-icon x-show="!printing" icon="lucide:printer" class="text-base"></iconify-icon>
                    <iconify-icon x-show="printing" x-cloak icon="mingcute:loading-fill" class="animate-spin text-lg"></iconify-icon>
                    <span x-text="printing ? 'Memproses...' : 'Selesai & Print Struk'"></span>
                </button>
            </div>
        </div>
    </x-mdal>
</div>