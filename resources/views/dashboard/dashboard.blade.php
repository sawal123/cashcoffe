<x-app-layout  :data="$data" :categories="$categories">
    @php
        $title = 'Dashboard';
        $subTitle = 'eCommerce';
        $script = '<script src="' . asset('assets/js/homethreeChart.js') . '"></script> ';
    @endphp

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
                        <div
                            class="w-[50px] h-[50px] {{ $item['color'] }} rounded-full flex justify-center items-center">
                            <iconify-icon icon="{{ $item['icon'] }}" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div><!-- card end -->
        @endforeach

    </div>
    {{-- {{ Auth::user()->name }} --}}

     <div class="grid grid-cols-1 gap-6 mt-6 xl:grid-cols-6 2xl:grid-cols-6">
        <div class="xl:col-span-12 2xl:col-span-6">
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
