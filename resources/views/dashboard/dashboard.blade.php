<x-app-layout>
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
                            <p class="font-medium text-neutral-900 dark:text-white mb-1">{{$item['title']}}</p>
                            <h6 class="mb-0 dark:text-white">{{ $item['value'] }}</h6>
                        </div>
                        <div class="w-[50px] h-[50px] {{ $item['color'] }} rounded-full flex justify-center items-center">
                            <iconify-icon icon="gridicons:multiple-users" class="text-white text-2xl mb-0"></iconify-icon>
                        </div>
                    </div>
                    <p class="font-medium text-sm text-neutral-600 dark:text-white mt-3 mb-0 flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1 text-success-600 dark:text-success-400"><iconify-icon
                                icon="bxs:up-arrow" class="text-xs"></iconify-icon> +4000</span>
                        Last 30 days users
                    </p>
                </div>
            </div><!-- card end -->
        @endforeach

    </div>



</x-app-layout>
