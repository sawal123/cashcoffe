<x-app-layout :data="$data" :categories="$categories">
    @php
    $title = 'Dashboard';
    $subTitle = 'eCommerce';
    $script = '<script src="' . asset('assets/js/homethreeChart.js') . '"></script> ';
    @endphp

    <a href="/order/create" wire:navigate
        class="fixed bottom-6 right-6 z-50 flex items-center justify-center w-14 h-14 bg-indigo-600 text-white rounded-full shadow-lg hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-300"
        aria-label="Buka Halaman Keranjang">
        <!-- Icon keranjang -->
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
            stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 6m12-6l2 6m-6-6v6" />
        </svg>
    </a>


    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 3xl:grid-cols-5 gap-6">
        @foreach ($cards as $item)
        <div
            class="card shadow-none border border-gray-200 dark:border-neutral-600 dark:bg-neutral-700 rounded-lg h-full bg-gradient-to-r from-cyan-600/10 to-bg-white">
            <div class="card-body p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="font-medium text-neutral-900 dark:text-white mb-1">{{ $item['title'] }}</p>
                        <h6 class="mb-0 dark:text-white">{{ $item['value'] }}</h6>
                    </div>
                    <div class="w-[50px] h-[50px] {{ $item['color'] }} rounded-full flex justify-center items-center">
                        <iconify-icon icon="{{ $item['icon'] }}" class="text-white text-2xl mb-0"></iconify-icon>
                    </div>
                </div>
            </div>
        </div><!-- card end -->
        @endforeach

    </div>







    <div class="grid grid-cols-1 gap-6 mt-6 xl:grid-cols-6 2xl:grid-cols-6">
        <div class="xl:col-span-12 2xl:col-span-6">
            <div class="my-6">
                <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    🔥 Menu Paling Laris
                </h2>
                <style>
                    .menu-swiper::part(pagination) {
                        position: relative;
                        margin-top: 16px;
                        /* ⬅️ jarak ke bawah */
                    }
                </style>
                <swiper-container class="mySwiper menu-swiper" slides-per-view="1.2" space-between="16" free-mode="true"
                    pagination="true" pagination-clickable="true" breakpoints='{
            "640": { "slidesPerView": 2.2 },
            "1024": { "slidesPerView": 5 }
        }'>
                    @foreach ($menuTerlaris as $menu)
                    <swiper-slide>
                        <div class=" rounded-xl shadow-sm border ">
                            <img src="{{ $menu->gambar ? asset('storage/' . $menu->gambar) : asset('images/default-menu.png') }}"
                                alt="{{ $menu->nama_menu }}" class="h-36 w-full rounded-xl object-cover">

                            <div class="p-3 text-center">
                                <h3 class="text-sm font-medium truncate">
                                    {{ $menu->nama_menu }}
                                </h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    Terjual
                                    <span class="font-semibold text-gray-800">
                                        {{ $menu->jumlah_terjual ?? 0 }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </swiper-slide>
                    @endforeach
                </swiper-container>
            </div>
            <div class="card h-full rounded-lg border-0">
                <div class="card-body">
                    <div class="flex flex-wrap items-center justify-between">
                        <h6 class="text-lg mb-0">Statik Penjualan</h6>
                        <select class="form-select bg-white dark:bg-neutral-700 form-select-sm w-auto">
                            <option>Yearly</option>
                            <option>Monthly</option>
                            <option>Weekly</option>
                            <option>Today</option>
                        </select>
                    </div>

                    <div id="chart" class="pt-[28px] apexcharts-tooltip-style-1"></div>
                </div>
            </div>
        </div>
    </div>



</x-app-layout>
