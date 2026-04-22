@props(['href' => '#', 'icon' => null, 'color' => 'blue', 'navigate' => true])

@php
    $colors = [
        'blue' => 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/30',
        'purple' => 'bg-purple-600 hover:bg-purple-700 shadow-purple-500/30',
        'success' => 'bg-success-600 hover:bg-success-700 shadow-success-500/30',
        'danger' => 'bg-danger-600 hover:bg-danger-700 shadow-danger-500/30',
    ];

    $colorClass = $colors[$color] ?? $colors['blue'];
@endphp

<a {{ $attributes->merge([
    'href' => $href,
    'class' => "inline-flex items-center justify-center px-5 py-2.5 $colorClass text-white text-sm font-bold rounded-2xl shadow-lg transition-all active:scale-95"
]) }} @if($navigate) wire:navigate @endif>
    @if($icon)
        <iconify-icon icon="{{ $icon }}" class="mr-2 text-lg align-middle"></iconify-icon>
    @endif
    {{ $slot }}
</a>
