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
            @endphp
            <article wire:click="addPesanan({{ $item->id }})"
                class="hover:shadow-xl cursor-pointer group flex flex-col rounded-xl overflow-hidden border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="h-40 md:h-52 overflow-hidden">
                    <img src="{{ asset('storage/' . $item->gambar) }}"
                        class="object-cover w-full h-full transition duration-500 ease-out group-hover:scale-105"
                        alt="Produk" />
                </div>
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
    
