<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" wire:navigate class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-400 hover:text-primary-600 transition-all shadow-sm">
                <iconify-icon icon="lucide:arrow-left" class="text-xl"></iconify-icon>
            </a>
            <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Diskon' }}</h6>
        </div>
        <x-breadcrumb :title="$title ?? 'Diskon'" />
    </div>
    <x-toast />
    @php
        $submit = $discountId ? 'update(' . $discountId . ')' : 'simpan';
        $button = $discountId ? 'Update' : 'Simpan';
    @endphp

    <form wire:submit.prevent="{{ $submit }}" class="flex flex-col gap-6 max-w-5xl">
        {{-- GENERAL INFORMATION --}}
        <div class="bg-neutral-50/50 dark:bg-neutral-800/50 p-6 sm:p-8 rounded-[2rem] border border-neutral-100 dark:border-neutral-700">
            <h3 class="text-xs font-black text-neutral-400 uppercase tracking-widest mb-6">General Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-ui.input wire:model="nama_diskon" label="Nama Diskon (Discount Name)" placeholder="e.g. Summer Sale 2024" required class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
                <x-ui.input wire:model="kode_diskon" label="Kode Diskon (Discount Code)" placeholder="e.g. SUMMER24" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700 uppercase" />
                
                <x-ui.select wire:model="jenis_diskon" label="Jenis Diskon (Discount Kind)" required class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700">
                    <option value="">Pilih jenis...</option>
                    <option value="persentase">Persentase (%)</option>
                    <option value="nominal">Nominal (Rp)</option>
                </x-ui.select>

                <div>
                    <label class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-2 block">Status</label>
                    <div class="flex items-center gap-4 mt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model="is_active" value="1" class="w-5 h-5 text-blue-600 focus:ring-blue-500 border-neutral-300">
                            <span class="text-sm font-medium px-3 py-1 bg-blue-500 text-white rounded-full">Active</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model="is_active" value="0" class="w-5 h-5 text-neutral-600 focus:ring-neutral-500 border-neutral-300">
                            <span class="text-sm font-medium px-3 py-1 bg-neutral-200 text-neutral-700 rounded-full">Inactive</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- CONFIGURATION & VALUE --}}
        <div class="bg-neutral-50/50 dark:bg-neutral-800/50 p-6 sm:p-8 rounded-[2rem] border border-neutral-100 dark:border-neutral-700">
            <h3 class="text-xs font-black text-neutral-400 uppercase tracking-widest mb-6">Configuration & Value</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-ui.select wire:model="type" label="Type Diskon (Discount Type)" required class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700">
                    <option value="general">General</option>
                    <option value="private">Private</option>
                </x-ui.select>

                <x-ui.input type="number" wire:model="nilai_diskon" label="Nilai Diskon (Discount Value)" placeholder="0" prefix="{{ $jenis_diskon === 'nominal' ? 'Rp' : '%' }}" required class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
                <x-ui.input type="number" wire:model="maksimum_diskon" label="Maksimum Diskon (Max Discount)" placeholder="0" prefix="Rp" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
                
                <x-ui.input type="number" wire:model="minimum_transaksi" label="Minimum Transaksi (Min Transaction)" placeholder="0" prefix="Rp" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
                <x-ui.input type="number" wire:model="limit" label="Limit (Usage Limit)" placeholder="e.g. 100" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
            </div>
        </div>

        {{-- SCOPE & VALIDITY --}}
        <div class="bg-neutral-50/50 dark:bg-neutral-800/50 p-6 sm:p-8 rounded-[2rem] border border-neutral-100 dark:border-neutral-700">
            <h3 class="text-xs font-black text-neutral-400 uppercase tracking-widest mb-6">Scope & Validity</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex flex-col relative">
                    <x-ui.select wire:model.live="scope" label="Scope Diskon (Discount Scope)" required class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700">
                        <option value="global">Global (All items)</option>
                        <option value="category">Specific Categories</option>
                        <option value="item">Specific Items</option>
                    </x-ui.select>
                    @if($scope !== 'global')
                        <button type="button" @click="$dispatch('open-modal', { name: 'scope-modal' })" class="mt-2 text-sm text-blue-600 hover:text-blue-700 font-semibold text-left">
                            <iconify-icon icon="lucide:settings-2" class="align-middle"></iconify-icon> Atur {{ $scope === 'item' ? 'Items' : 'Categories' }} ({{ count($selectedItems) }} selected)
                        </button>
                    @endif
                </div>

                <x-ui.input type="date" wire:model="tanggal_mulai" label="Tanggal Mulai (Start Date)" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
                <x-ui.input type="date" wire:model="tanggal_akhir" label="Tanggal Akhir (End Date)" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />

                <x-ui.select wire:model="branch_id" label="Cabang Khusus (Special Branch)" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700 md:col-span-1">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}">{{ $b->nama_branch }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select wire:model="price_tier_id" label="Price Tier Khusus (Special Price Tier)" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700 md:col-span-2">
                    <option value="">All Tiers</option>
                    @foreach($priceTiers as $pt)
                    <option value="{{ $pt->id }}">{{ $pt->nama_tier }}</option>
                    @endforeach
                </x-ui.select>
            </div>
        </div>

        {{-- ACTIONS --}}
        <div class="flex items-center justify-end gap-4 mt-2">
            <a href="/discount" class="px-8 py-3 bg-white text-blue-600 border border-blue-200 font-bold rounded-2xl transition-all hover:bg-neutral-50 active:scale-95">Batal</a>
            <x-ui.button type="submit" color="blue">
                {{ $button }} Diskon
            </x-ui.button>
        </div>
    </form>

    {{-- MODAL --}}
    <x-mdal name="scope-modal">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-neutral-900 dark:text-white">Pilih {{ $scope === 'item' ? 'Menu' : 'Kategori' }}</h3>
            </div>
            
            <div class="max-h-80 overflow-y-auto pr-2 space-y-2">
                @if($scope === 'item')
                    @foreach($menus as $m)
                    <label class="flex items-center gap-3 p-3 rounded-xl border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 cursor-pointer transition">
                        <input type="checkbox" wire:model.live="selectedItems" value="{{ $m->id }}" class="w-5 h-5 text-blue-600 rounded border-neutral-300 focus:ring-blue-500">
                        <span class="font-medium text-neutral-700 dark:text-neutral-300">{{ $m->nama_menu }}</span>
                    </label>
                    @endforeach
                @elseif($scope === 'category')
                    @foreach($categories as $c)
                    <label class="flex items-center gap-3 p-3 rounded-xl border border-neutral-200 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800 cursor-pointer transition">
                        <input type="checkbox" wire:model.live="selectedItems" value="{{ $c->id }}" class="w-5 h-5 text-blue-600 rounded border-neutral-300 focus:ring-blue-500">
                        <span class="font-medium text-neutral-700 dark:text-neutral-300">{{ $c->nama }}</span>
                    </label>
                    @endforeach
                @endif
            </div>

            <div class="mt-6 flex justify-end">
                <x-ui.button @click="$dispatch('close-modal', { name: 'scope-modal' })" color="blue" class="w-full">
                    Selesai
                </x-ui.button>
            </div>
        </div>
    </x-mdal>

</div>
