@props(['color' => 'blue'])

@php
    $colors = [
        'blue' => 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/30',
        'purple' => 'bg-purple-600 hover:bg-purple-700 shadow-purple-500/30 font-bold',
        'success' => 'bg-success-600 hover:bg-success-700 shadow-success-500/30',
        'danger' => 'bg-danger-600 hover:bg-danger-700 shadow-danger-500/30',
    ];

    $colorClass = $colors[$color] ?? $colors['blue'];
@endphp

<button
    {{ $attributes->merge([
        'type' => 'button',
        'class' => "px-8 py-3 $colorClass text-white rounded-2xl shadow-lg transition-all active:scale-95",
    ]) }}>
    {{ $slot }}
</button>
