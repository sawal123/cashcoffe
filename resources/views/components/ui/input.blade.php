@props(['label' => null, 'prefix' => null, 'suffix' => null])

<div>
    @if ($label)
        <label class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-2 block">
            {{ $label }}
        </label>
    @endif
    <div class="relative">
        <input {{ $attributes->merge([
    'class' =>
        'w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-2xl py-3 placeholder:text-neutral-400 focus:ring-2 focus:ring-blue-500',
    'style' =>
        ($prefix ? 'padding-left: 60px;' : 'padding-left: 1rem;') .
        ($suffix ? 'padding-right: 60px;' : 'padding-right: 1rem;'),
]) }}>

        @if ($prefix)
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-bold text-neutral-400 flex items-center">
                {!! $prefix !!}
            </span>
        @endif

        @if ($suffix)
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-neutral-400 uppercase">
                {{ $suffix }}
            </span>
        @endif
    </div>
    @error($attributes->get('wire:model') ?? $attributes->get('name'))
        <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span>
    @enderror
</div>