@props([
    'model',
    'options',
    'activeValue' => null,
    'valueKey' => 'id',
    'labelKey' => 'nama',
    'placeholder' => 'Pilih...',
    'icon' => 'mingcute:filter-line'
])

@php
    $activeLabel = $placeholder;
    if ($activeValue !== null && $activeValue !== '') {
        $found = collect($options)->first(function($item) use ($valueKey, $activeValue) {
            return (is_array($item) ? $item[$valueKey] : $item->{$valueKey}) == $activeValue;
        });
        if ($found) {
            $activeLabel = is_array($found) ? $found[$labelKey] : $found->{$labelKey};
        }
    }
@endphp

<div class="relative" x-data="{ open: false }">
    <button type="button" @click="open = !open" 
        class="h-[46px] px-5 flex items-center justify-between gap-2 bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-2xl text-sm font-bold text-neutral-700 dark:text-neutral-300 hover:border-blue-500 transition-all active:scale-95 shadow-sm min-w-[200px]">
        <div class="flex items-center gap-2">
            <iconify-icon icon="{{ $icon }}" class="text-lg text-neutral-400"></iconify-icon>
            <span>{{ $activeLabel }}</span>
        </div>
        <iconify-icon icon="mingcute:down-line" class="text-neutral-400 transition-transform" :class="open ? 'rotate-180' : ''"></iconify-icon>
    </button>

    <div x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="z-50 bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-2xl shadow-xl w-56 absolute mt-2 left-0 overflow-hidden" style="display: none;">
        <div class="p-1 max-h-[300px] overflow-y-auto">
            <button type="button" wire:click="$set('{{ $model }}', '')" @click="open = false"
                class="w-full text-left px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors {{ !$activeValue ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30' : 'text-neutral-600 dark:text-neutral-400' }}">
                {{ $placeholder }}
            </button>
            <div class="my-1 border-t border-neutral-100 dark:border-neutral-700"></div>
            @foreach ($options as $opt)
                @php
                    $optValue = is_array($opt) ? $opt[$valueKey] : $opt->{$valueKey};
                    $optLabel = is_array($opt) ? $opt[$labelKey] : $opt->{$labelKey};
                @endphp
                <button type="button" wire:click="$set('{{ $model }}', '{{ $optValue }}')" @click="open = false"
                    class="w-full text-left px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors {{ $activeValue == $optValue ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30' : 'text-neutral-600 dark:text-neutral-400' }}">
                    {{ $optLabel }}
                </button>
            @endforeach
        </div>
    </div>
</div>
