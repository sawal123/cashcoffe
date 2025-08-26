@props(['label' => '', 'place' => '', 'type' => 'text', 'required' => false])

@php
    $target = collect($attributes->getAttributes())
        ->filter(fn($v, $k) => Str::startsWith($k, 'wire:model'))
        ->values()
        ->first();

@endphp

<div class="mb-2 relative">

    @if ($label)
        <label class="form-label">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <input type="{{ $type }}" placeholder="{{ $place }}"
        {{ $attributes->class([
            'w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:border-blue-500 focus:ring focus:ring-blue-200 pr-10',
        ]) }}>

    {{-- Loading spinner (munculwq saat request jalan untuk properti target) --}}
    <div wire:loading wire:target="{{ $target }}"
        class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2">
        {{-- <svg class="h-5 w-5 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
        </svg> --}}
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" class="h-5 w-5 animate-spin text-blue-500">
            <radialGradient id="a12" cx=".66" fx=".66" cy=".3125" fy=".3125"
                gradientTransform="scale(1.5)">
                <stop offset="0" stop-color="#FF156D"></stop>
                <stop offset=".3" stop-color="#FF156D" stop-opacity=".9"></stop>
                <stop offset=".6" stop-color="#FF156D" stop-opacity=".6"></stop>
                <stop offset=".8" stop-color="#FF156D" stop-opacity=".3"></stop>
                <stop offset="1" stop-color="#FF156D" stop-opacity="0"></stop>
            </radialGradient>
            <circle transform-origin="center" fill="none" stroke="url(#a12)" stroke-width="15" stroke-linecap="round"
                stroke-dasharray="200 1000" stroke-dashoffset="0" cx="100" cy="100" r="70">
                <animateTransform type="rotate" attributeName="transform" calcMode="spline" dur="2"
                    values="360;0" keyTimes="0;1" keySplines="0 0 1 1" repeatCount="indefinite"></animateTransform>
            </circle>
            <circle transform-origin="center" fill="none" opacity=".2" stroke="#FF156D" stroke-width="15"
                stroke-linecap="round" cx="100" cy="100" r="70"></circle>
        </svg>
    </div>
</div>
