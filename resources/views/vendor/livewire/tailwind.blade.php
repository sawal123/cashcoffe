@if ($paginator->hasPages())
    <ul class="pagination flex flex-wrap  items-center justify-end mt-6">
        {{-- Tombol Previous --}}
        <li class="page-item">
            @if ($paginator->onFirstPage())
                <span
                    class="page-link  bg-gray-200   dark:bg-gray-600 text-gray-400 font-medium border-0 px-5 py-2.5 flex items-center justify-center h-[48px] w-[48px] rounded-s-lg">
                    <iconify-icon icon="iconamoon:arrow-left-2-light" class="text-2xl"></iconify-icon>
                </span>
            @else
                <button wire:click="previousPage" wire:loading.attr="disabled"
                    class="page-link bg-primary-50 dark:bg-primary-600/25 text-secondary-light font-medium border-0 px-5 py-2.5 flex items-center justify-center h-[48px] w-[48px] rounded-s-lg">
                    <iconify-icon icon="iconamoon:arrow-left-2-light" class="text-2xl"></iconify-icon>
                </button>
            @endif
        </li>

        {{-- Nomor Halaman --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="page-item">
                    <span class="page-link px-5 py-2.5 h-[48px] w-[48px] flex items-center justify-center">{{ $element }}</span>
                </li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    <li class="page-item">
                        @if ($page == $paginator->currentPage())
                            <span
                                class="page-link bg-primary-600   text-white font-medium border-0 px-5 py-2.5 flex items-center justify-center h-[48px] w-[48px]">
                                {{ $page }}
                            </span>
                        @else
                            <button wire:click="gotoPage({{ $page }})"
                                class="page-link bg-primary-50  dark:bg-primary-600/25 text-secondary-light font-medium border-0 px-5 py-2.5 flex items-center justify-center h-[48px] w-[48px]">
                                {{ $page }}
                            </button>
                        @endif
                    </li>
                @endforeach
            @endif
        @endforeach

        {{-- Tombol Next --}}
        <li class="page-item">
            @if ($paginator->hasMorePages())
                <button wire:click="nextPage" wire:loading.attr="disabled"
                    class="page-link bg-primary-50 dark:bg-primary-600/25 text-secondary-light font-medium border-0 px-5 py-2.5 flex items-center justify-center h-[48px] w-[48px] rounded-e-lg">
                    <iconify-icon icon="iconamoon:arrow-right-2-light" class="text-2xl"></iconify-icon>
                </button>
            @else
                <span
                    class="page-link bg-gray-200 dark:bg-gray-600/25 text-gray-400 font-medium border-0 px-5 py-2.5 flex items-center justify-center h-[48px] w-[48px] rounded-e-lg">
                    <iconify-icon icon="iconamoon:arrow-right-2-light" class="text-2xl"></iconify-icon>
                </span>
            @endif
        </li>
    </ul>
@endif
