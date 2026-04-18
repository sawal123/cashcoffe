@props([
    'title' => null,
    'value' => null,
    'icon' => null,
    'subtext' => null,
    'color' => 'white',
    'trending' => false,
])

<div
    {{ $attributes->merge([
        'class' =>
            'rounded-[2rem] p-6 transition-all flex flex-col gap-3 relative overflow-hidden group ' .
            ($color === 'blue'
                ? 'bg-blue-600 text-white shadow-xl shadow-blue-500/20'
                : 'bg-white dark:bg-neutral-800 border-2 border-neutral-50 dark:border-neutral-700 shadow-sm hover:shadow-md'),
    ]) }}>
    @if ($color === 'blue')
        <div
            class="absolute -right-6 -top-6 w-32 h-32 bg-white/10 rounded-full blur-3xl group-hover:scale-125 transition-all duration-700">
        </div>
    @endif

    <div class="relative z-10 flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span
                class="text-[9px] font-black uppercase tracking-[0.2em] {{ $color === 'blue' ? 'opacity-70 text-white' : 'text-neutral-400 dark:text-neutral-500' }}">
                {{ $title }}
            </span>
            @if ($icon)
                <div
                    class="w-10 h-10 {{ $color === 'blue' ? 'bg-white/20 border border-white/10' : 'bg-neutral-50 dark:bg-neutral-700 text-neutral-500' }} rounded-2xl flex items-center justify-center backdrop-blur-md">
                    <iconify-icon icon="{{ $icon }}" class="text-lg"></iconify-icon>
                </div>
            @endif
        </div>
        <div>
            <h3
                class="text-xl font-black italic tracking-tight {{ $color === 'blue' ? 'text-white' : 'text-neutral-900 dark:text-neutral-100' }}">
                {{ $value }}
            </h3>

            @if ($subtext)
                <div
                    class="mt-1 flex items-center gap-1.5 text-[9px] font-black {{ $color === 'blue' ? 'text-blue-100 opacity-80' : 'text-blue-600 dark:text-blue-400 uppercase tracking-widest' }}">
                    @if ($trending)
                        <iconify-icon icon="lucide:trending-up"></iconify-icon>
                    @elseif($color !== 'blue')
                        <iconify-icon icon="lucide:check-circle-2"></iconify-icon>
                    @endif
                    <span>{{ $subtext }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
