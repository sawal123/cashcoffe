<div class="order-create-page flex flex-col gap-6 w-full"
    x-data="cashcoffeOrderCart({
        cart: @js($pesanan),
        discount: @js($frontendDiscount),
        discountAmount: @js($discountValue),
        discountCode: @js($discount),
        status: @js($status),
        storageBase: @js(asset('storage')),
        defaultImage: @js(asset('images/default-menu.png')),
    })"
    x-init="init()"
    x-on:order-cart-state-updated.window="applyServerState($event.detail)">
    <script data-navigate-once>
        window.cashcoffeOrderCart = window.cashcoffeOrderCart || function (initial) {
            return {
                cart: {},
                discountConfig: initial.discount || null,
                discountCode: initial.discountCode || '',
                serverDiscountAmount: Number(initial.discountAmount || 0),
                variantMenu: null,
                selectedOptions: {},
                variantOpen: false,
                syncing: false,
                status: initial.status || null,
                storageBase: initial.storageBase || '/storage',
                defaultImage: initial.defaultImage || '/images/default-menu.png',

                init() {
                    this.cart = this.normalizeCart(initial.cart || {});
                    this.emitTotal();
                },

                normalizeCart(source) {
                    const normalized = {};
                    const entries = Array.isArray(source)
                        ? source.map((item, index) => [item.key || String(index), item])
                        : Object.entries(source || {});

                    entries.forEach(([key, item]) => {
                        const cartKey = String(item.key || key);
                        normalized[cartKey] = {
                            ...item,
                            key: cartKey,
                            id: Number(item.id),
                            harga: Number(item.harga || 0),
                            qty: Math.max(1, Number(item.qty || 1)),
                            category_id: Number(item.category_id || 0),
                            selected_options: Array.isArray(item.selected_options) ? item.selected_options : [],
                            display_options: Array.isArray(item.display_options) ? item.display_options : [],
                        };
                    });

                    return normalized;
                },

                cartPayload() {
                    const payload = {};

                    Object.entries(this.cart).forEach(([key, item]) => {
                        const { key: ignoredKey, ...data } = item;
                        payload[key] = data;
                    });

                    return payload;
                },

                syncCartToLivewire() {
                    if (this.$wire) {
                        this.$wire.set('pesanan', this.cartPayload(), false);
                    }
                },

                cartItems() {
                    return Object.values(this.cart);
                },

                formatMoney(value) {
                    return new Intl.NumberFormat('id-ID').format(Math.round(Number(value || 0)));
                },

                imageUrl(item) {
                    if (item.image_url) return item.image_url;
                    return item.gambar ? `${this.storageBase}/${item.gambar}` : this.defaultImage;
                },

                subtotal() {
                    return this.cartItems().reduce((sum, item) => sum + (item.harga * item.qty), 0);
                },

                isDiscountEligible(item) {
                    const discount = this.discountConfig;
                    if (!discount || discount.scope === 'global') return true;

                    return (discount.items || []).some((rule) => {
                        const modelType = String(rule.model_type || '');
                        return (modelType.includes('Menu') && Number(rule.model_id) === Number(item.id))
                            || (modelType.includes('Category') && Number(rule.model_id) === Number(item.category_id));
                    });
                },

                itemDiscount(item) {
                    const discount = this.discountConfig;
                    if (!discount || discount.scope === 'global' || !this.isDiscountEligible(item)) return 0;

                    if (discount.jenis === 'persentase') {
                        let amount = Math.round(item.harga * item.qty * (Number(discount.nilai || 0) / 100));
                        if (Number(discount.maksimum || 0) > 0) {
                            amount = Math.min(amount, Number(discount.maksimum));
                        }
                        return amount;
                    }

                    return Number(discount.nilai || 0) * item.qty;
                },

                discountAmount() {
                    const discount = this.discountConfig;
                    if (!discount || !this.discountCode) return 0;

                    const subtotal = this.subtotal();
                    if (discount.scope === 'global') {
                        if (Number(discount.minimum || 0) > subtotal) return 0;

                        let amount = discount.jenis === 'persentase'
                            ? Math.round(subtotal * (Number(discount.nilai || 0) / 100))
                            : Number(discount.nilai || 0);

                        if (Number(discount.maksimum || 0) > 0) {
                            amount = Math.min(amount, Number(discount.maksimum));
                        }

                        return Math.min(amount, subtotal);
                    }

                    return Math.min(
                        this.cartItems().reduce((sum, item) => sum + this.itemDiscount(item), 0),
                        subtotal,
                    );
                },

                totalAfterDiscount() {
                    return Math.max(0, this.subtotal() - this.discountAmount());
                },

                emitTotal() {
                    window.dispatchEvent(new CustomEvent('order-preview-total-updated', {
                        detail: { total: this.totalAfterDiscount() },
                    }));
                },

                addMenu(menu) {
                    if (this.status === 'selesai') return;

                    if ((menu.variantGroups || []).length > 0) {
                        this.variantMenu = menu;
                        this.selectedOptions = {};
                        this.variantOpen = true;
                        return;
                    }

                    this.upsertMenu(menu, [], []);
                },

                upsertMenu(menu, optionIds, displayOptions) {
                    const ids = [...optionIds].map(Number).sort((a, b) => a - b);
                    const key = ids.length ? `${menu.id}_${ids.join('-')}` : String(menu.id);

                    if (this.cart[key]) {
                        this.cart[key].qty++;
                    } else {
                        this.cart[key] = {
                            key,
                            id: Number(menu.id),
                            nama_menu: menu.nama_menu,
                            harga: Number(menu.harga || 0) + ids.reduce((sum, id) => {
                                const option = (menu.variantGroups || []).flatMap((group) => group.options || [])
                                    .find((candidate) => Number(candidate.id) === id);
                                return sum + Number(option?.extra_price || 0);
                            }, 0),
                            gambar: menu.gambar || '',
                            image_url: menu.image_url || '',
                            category_id: Number(menu.category_id || 0),
                            qty: 1,
                            selected_options: ids,
                            display_options: displayOptions,
                        };
                    }

                    this.syncCartToLivewire();
                    this.emitTotal();
                },

                changeQty(key, delta) {
                    if (this.status === 'selesai' || !this.cart[key]) return;

                    this.cart[key].qty += delta;
                    if (this.cart[key].qty <= 0) delete this.cart[key];

                    this.syncCartToLivewire();
                    this.emitTotal();
                },

                toggleOption(group, option) {
                    const groupKey = String(group.id);
                    const current = this.selectedOptions[groupKey] || [];

                    if (group.selection_type === 'single') {
                        this.selectedOptions[groupKey] = [Number(option.id)];
                    } else if (current.includes(Number(option.id))) {
                        this.selectedOptions[groupKey] = current.filter((id) => id !== Number(option.id));
                    } else {
                        this.selectedOptions[groupKey] = [...current, Number(option.id)];
                    }
                },

                isOptionSelected(groupId, optionId) {
                    return (this.selectedOptions[String(groupId)] || []).includes(Number(optionId));
                },

                variantExtra() {
                    return Object.values(this.selectedOptions).flat().reduce((sum, optionId) => {
                        const option = (this.variantMenu?.variantGroups || []).flatMap((group) => group.options || [])
                            .find((candidate) => Number(candidate.id) === Number(optionId));
                        return sum + Number(option?.extra_price || 0);
                    }, 0);
                },

                missingRequiredGroup() {
                    return (this.variantMenu?.variantGroups || []).find((group) => {
                        return group.is_required && !(this.selectedOptions[String(group.id)] || []).length;
                    });
                },

                confirmVariant() {
                    const missing = this.missingRequiredGroup();
                    if (missing) {
                        this.showToast(`Pilih ${missing.nama_group} terlebih dahulu`, 'warning');
                        return;
                    }

                    const optionIds = Object.values(this.selectedOptions).flat().map(Number);
                    const displayOptions = optionIds.map((optionId) => {
                        const option = (this.variantMenu?.variantGroups || []).flatMap((group) => group.options || [])
                            .find((candidate) => Number(candidate.id) === Number(optionId));
                        return option?.nama_opsi || '';
                    }).filter(Boolean);

                    this.upsertMenu(this.variantMenu, optionIds, displayOptions);
                    this.closeVariant();
                },

                closeVariant() {
                    this.variantOpen = false;
                    this.variantMenu = null;
                    this.selectedOptions = {};
                },

                showToast(message, type = 'info') {
                    window.dispatchEvent(new CustomEvent('showToast', {
                        detail: { message, type, title: 'Pesanan' },
                    }));
                },

                applyServerState(detail) {
                    if (!detail) return;

                    if (detail.cart) this.cart = this.normalizeCart(detail.cart);
                    this.discountConfig = detail.discount || null;
                    this.discountCode = detail.discountCode || '';
                    this.serverDiscountAmount = Number(detail.discountAmount || 0);
                    this.syncing = false;
                    this.emitTotal();
                },

                isSubmitDisabled(wire) {
                    if (this.syncing || this.cartItems().length === 0) return true;
                    if (!wire.nama_costumer || !wire.metode_pembayaran) return true;

                    return Number(wire.metode_pembayaran) === Number(wire.cashMethodId)
                        && (!wire.uang_tunai || Number(wire.uang_tunai) < this.totalAfterDiscount());
                },

                async submitOrder(wire, event, method) {
                    event.preventDefault();
                    if (this.isSubmitDisabled(wire)) return;

                    this.syncing = true;
                    this.syncCartToLivewire();

                    try {
                        await wire.call(method);
                    } finally {
                        this.syncing = false;
                    }
                },
            };
        };
    </script>

    <style>
        .order-create-page {
            width: 100%;
            min-width: 0;
        }

        .order-success-check {
            display: inline-flex;
            width: 4.5rem;
            height: 4.5rem;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(74, 222, 128, .35);
            border-radius: 9999px;
            background: rgba(34, 197, 94, .14);
            box-shadow: 0 0 0 8px rgba(34, 197, 94, .06), 0 14px 28px rgba(34, 197, 94, .16);
            animation: order-success-pop .45s cubic-bezier(.34, 1.56, .64, 1) both;
        }

        .order-success-check svg {
            width: 2.75rem;
            height: 2.75rem;
        }

        .order-success-check circle {
            fill: none;
            stroke: #4ade80;
            stroke-width: 2.5;
            stroke-dasharray: 138;
            stroke-dashoffset: 138;
            animation: order-success-circle .55s ease-out .08s forwards;
        }

        .order-success-check path {
            fill: none;
            stroke: #bbf7d0;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 3.5;
            stroke-dasharray: 38;
            stroke-dashoffset: 38;
            animation: order-success-checkmark .35s ease-out .45s forwards;
        }

        @keyframes order-success-pop {
            from { opacity: 0; transform: scale(.55); }
            to { opacity: 1; transform: scale(1); }
        }

        @keyframes order-success-circle {
            to { stroke-dashoffset: 0; }
        }

        @keyframes order-success-checkmark {
            to { stroke-dashoffset: 0; }
        }

        @media (prefers-reduced-motion: reduce) {
            .order-success-check,
            .order-success-check circle,
            .order-success-check path {
                animation: none;
                stroke-dashoffset: 0;
            }
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

        .order-create-page .order-category-options-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, .65) transparent;
        }

        .order-create-page .order-category-options-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .order-create-page .order-category-options-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .order-create-page .order-category-options-scroll::-webkit-scrollbar-thumb {
            border-radius: 9999px;
            background: rgba(148, 163, 184, .65);
        }

        :is(.dark .order-create-page .order-category-options-scroll) {
            scrollbar-color: rgba(148, 163, 184, .55) transparent;
        }

        :is(.dark .order-create-page .order-category-options-scroll::-webkit-scrollbar-thumb) {
            background: rgba(148, 163, 184, .55);
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
                    placeholder="Semua Menu" scrollClass="order-category-options-scroll" />
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
                                    $menuPayload = [
                                        'id' => (int) $item->id,
                                        'nama_menu' => $item->nama_menu,
                                        'harga' => (int) $harga,
                                        'gambar' => $item->gambar,
                                        'image_url' => $item->gambar ? asset('storage/' . $item->gambar) : asset('images/default-menu.png'),
                                        'category_id' => (int) $item->categories_id,
                                        'variantGroups' => $item->variantGroups->map(fn ($group) => [
                                            'id' => (int) $group->id,
                                            'nama_group' => $group->nama_group,
                                            'is_required' => (bool) $group->is_required,
                                            'selection_type' => $group->selection_type,
                                            'options' => $group->options->map(fn ($option) => [
                                                'id' => (int) $option->id,
                                                'nama_opsi' => $option->nama_opsi,
                                                'extra_price' => (int) ($option->variantPrices->first()?->extra_price ?? 0),
                                            ])->values()->all(),
                                        ])->values()->all(),
                                    ];
                                @endphp
                                <article @click="addMenu(@js($menuPayload))"
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
    <template x-teleport="body">
    <div x-show="variantOpen" x-cloak
        class="fixed inset-0 z-[999999] flex items-center justify-center overflow-y-auto bg-black/60 p-4 backdrop-blur-sm"
        x-transition.opacity @click.self="closeVariant()" @keydown.escape.window="closeVariant()">
        <div x-show="variantOpen" x-transition
            class="my-auto w-full max-w-xl rounded-[2rem] border border-neutral-200 bg-white p-6 shadow-2xl dark:border-neutral-700 dark:bg-neutral-800 sm:p-8"
            @click.stop>
            <div class="mb-8 flex items-center gap-5 border-b border-neutral-100 pb-6 dark:border-neutral-700">
                <img :src="variantMenu ? imageUrl(variantMenu) : defaultImage" alt=""
                    class="h-20 w-20 rounded-[1.5rem] border-2 border-white object-cover shadow-lg dark:border-neutral-800">
                <div class="min-w-0">
                    <h3 class="truncate text-xl font-black text-neutral-900 dark:text-white" x-text="variantMenu?.nama_menu"></h3>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-widest text-neutral-400">Harga Dasar:</span>
                        <span class="text-lg font-black text-blue-600 dark:text-blue-400"
                            x-text="'Rp ' + formatMoney(variantMenu?.harga)"></span>
                    </div>
                </div>
            </div>

            <div x-show="variantMenu && variantMenu.variantGroups.some(group => group.is_required)"
                class="mb-6 flex items-center gap-3 rounded-2xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/20">
                <iconify-icon icon="mingcute:warning-line" class="text-xl text-amber-500"></iconify-icon>
                <p class="text-xs font-bold text-amber-700 dark:text-amber-300">Pilih opsi wajib bertanda bintang (*).</p>
            </div>

            <div class="space-y-8">
                <template x-for="group in (variantMenu?.variantGroups || [])" :key="group.id">
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <label class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-neutral-400">
                                <span x-text="group.nama_group"></span>
                                <span x-show="group.is_required" class="-mt-1 text-lg font-black text-red-500">*</span>
                            </label>
                            <span x-show="group.selection_type === 'multiple'"
                                class="rounded-lg border border-blue-100 bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-500 dark:border-blue-800 dark:bg-blue-900/30">Bisa pilih banyak</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <template x-for="option in group.options" :key="option.id">
                                <button type="button" @click="toggleOption(group, option)"
                                    class="relative flex flex-col overflow-hidden rounded-[1.5rem] border-2 p-4 text-left transition-all"
                                    :class="isOptionSelected(group.id, option.id)
                                        ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-500/20'
                                        : 'border-neutral-100 bg-white text-neutral-700 hover:border-blue-300 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300'">
                                    <div class="mb-1 flex items-center justify-between">
                                        <span class="text-sm font-black leading-tight" x-text="option.nama_opsi"></span>
                                        <iconify-icon x-show="isOptionSelected(group.id, option.id)"
                                            icon="mingcute:check-circle-fill" class="text-xl text-white"></iconify-icon>
                                    </div>
                                    <span class="text-[11px] font-bold"
                                        :class="isOptionSelected(group.id, option.id) ? 'text-blue-100' : 'text-blue-600 dark:text-blue-400'"
                                        x-text="Number(option.extra_price || 0) > 0 ? '+Rp ' + formatMoney(option.extra_price) : 'Gratis'"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-10 border-t border-neutral-100 pt-8 dark:border-neutral-700">
                <div class="mb-6 flex items-center justify-between">
                    <span class="text-xs font-black uppercase tracking-widest text-neutral-400">Estimasi Total:</span>
                    <span class="text-2xl font-black text-neutral-900 dark:text-white"
                        x-text="'Rp ' + formatMoney(Number(variantMenu?.harga || 0) + variantExtra())"></span>
                </div>
                <div class="flex gap-4">
                    <button type="button" @click="closeVariant()"
                        class="flex-1 rounded-2xl border border-neutral-200 px-6 py-4 text-sm font-bold text-neutral-600 transition hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-700">Batal</button>
                    <button type="button" @click="confirmVariant()" :disabled="missingRequiredGroup()"
                        class="flex-[2] rounded-2xl bg-blue-600 px-6 py-4 text-sm font-bold text-white shadow-xl shadow-blue-500/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">Tambahkan ke Pesanan</button>
                </div>
            </div>
        </div>
    </div>
    </template>

    {{-- MODAL SUCCESS / DETAIL PESANAN --}}
    <x-mdal name="order-success" wireModel="showOrderSuccess">
        <div class="px-6 pb-6" x-data="{ printing: false }">
            <div class="flex justify-center pb-4" aria-label="Pesanan berhasil" role="img">
                <span class="order-success-check">
                    <svg viewBox="0 0 52 52" aria-hidden="true">
                        <circle cx="26" cy="26" r="22"></circle>
                        <path d="M15 27.5 22.5 35 38 18"></path>
                    </svg>
                </span>
            </div>
            <div class="flex items-start justify-between gap-4 border-b border-neutral-100 pb-5 dark:border-neutral-700">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-neutral-400">Pesanan Berhasil Diproses</span>
                    <h3 class="mt-1 text-xl font-black text-neutral-900 dark:text-neutral-100">
                        #{{ $lastKodePesanan ?: '-' }}
                    </h3>
                    <p class="mt-1 text-xs font-medium text-neutral-400">{{ $lastCreatedAt ?: now()->format('d M Y H:i') }}</p>
                </div>
                @php
                    $lastStatusClass = $lastStatusPesanan === 'selesai'
                        ? 'border-emerald-400/30 bg-emerald-500/15 text-emerald-300'
                        : ($lastStatusPesanan === 'dibatalkan'
                            ? 'border-rose-400/30 bg-rose-500/15 text-rose-300'
                            : 'border-amber-400/30 bg-amber-500/15 text-amber-200');
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

                        $wire.printLastOrder()
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
                    <span x-text="printing ? 'Membuka Printer...' : 'Cetak Struk'"></span>
                </button>
            </div>
        </div>
    </x-mdal>
</div>
