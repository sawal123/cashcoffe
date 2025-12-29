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
            <button wire:click="$set('selectedCategoryId', null)" class="px-3 py-1 rounded-full border border-slate-300 text-sm bg-white text-slate-800">
                Semua
            </button>
            @foreach ($categories as $category)
            <button wire:click="filterByCategory({{ $category->id }})" class="px-3 py-1 rounded-full border border-slate-300 text-sm
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
            $isAvailable = $item->stokTersedia();
            @endphp

            <article @if($isAvailable) wire:click="addPesanan({{ $item->id }})" @endif class="group flex flex-col rounded-xl overflow-hidden border
        {{ $isAvailable
            ? 'cursor-pointer hover:shadow-xl bg-white dark:bg-slate-800'
            : 'opacity-50 cursor-not-allowed bg-slate-100 dark:bg-slate-700'
        }}">
                <div class="h-40 md:h-52 overflow-hidden relative">
                    <img src="{{ asset('storage/' . $item->gambar) }}" class="object-cover w-full h-full" />

                    @unless($isAvailable)
                    <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                        <span class="text-white text-sm font-semibold">
                            Stok Habis
                        </span>
                    </div>
                    @endunless
                </div>

                <div class="p-3">
                    <p class="text-sm font-medium">{{ $item->nama_menu }}</p>
                    <p class="font-bold">
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


    <x-mdal name="order-success">
        <div class="px-6 py-5 text-center">

            {{-- ICON --}}
            <div class="mx-auto mb-3 w-12 h-12 flex items-center justify-center rounded-full bg-green-100 text-green-600">
                <iconify-icon icon="mingcute:check-circle-line" class="text-2xl"></iconify-icon>
            </div>

            {{-- TITLE --}}
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-1">
                Pesanan Berhasil
            </h3>

            <p class="text-sm text-slate-500 mb-5">
                Pesanan telah berhasil diproses
            </p>
            {{-- INFO --}}
            <div class="bg-slate-50 dark:bg-slate-800 rounded-lg p-4 text-left text-sm mb-6">
                <div class="flex justify-between mb-2">
                    <span class="text-slate-600">Kode Pesanan</span>
                    <span class="font-semibold text-slate-800 dark:text-white">
                        {{ $lastKodePesanan }}
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-slate-600">Total</span>
                    <span class="font-bold text-lg text-slate-900 dark:text-white">
                        Rp {{ number_format($lastTotalPesanan, 0, ',', '.') }}
                    </span>
                </div>
            </div>
            {{-- ACTION --}}
            <div class="flex justify-center gap-3">
                {{-- TUTUP --}}
                <button type="button" x-on:click="modalIsOpen = false" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 text-sm font-medium transition">
                    Tutup
                </button>
                {{-- CETAK --}}
                @if($lastPesananId)
                <a href="{{ route('struk.print', base64_encode($lastPesananId)) }}" target="_blank" class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700 text-sm font-medium transition inline-flex items-center">
                    <iconify-icon icon="mingcute:printer-line" class="mr-1"></iconify-icon>
                    Cetak Struk
                </a>
                @endif
            </div>
        </div>
    </x-mdal>
</div>
