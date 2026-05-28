@props(['perPage' => null])
<div class="relative inline-block text-left" x-data="{ open: false }">
    <button type="button" @click="open = !open" @click.outside="open = false"
        class="text-primary-600 h-[41.6px] focus:bg-primary-600 hover:bg-primary-700 border border-primary-600 hover:text-white focus:text-white font-medium rounded-lg px-4 text-center inline-flex items-center"
        :class="{ 'bg-primary-600 text-white': open }">
        {{ $perPage }}
        <i class="ri-arrow-down-s-line ms-3 text-base leading-none transition-transform" :class="{ 'rotate-180': open }"></i>
    </button>

    <!-- Dropdown menu -->
    <div x-cloak x-show="open"
        class="absolute left-0 mt-2 z-50 bg-white divide-y divide-gray-100 rounded-lg shadow-2xl w-44 dark:bg-gray-700">
        <ul class="py-2 text-base text-gray-700 dark:text-gray-200">
            @foreach ([5, 10, 50, 100] as $size)
                <li>
                    <a href="javascript:void(0)" wire:click="$set('perPage', {{ $size }})" @click="open = false"
                        class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                        {{ $size }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
