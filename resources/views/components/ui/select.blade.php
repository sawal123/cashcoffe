@props(['label' => null])

<div>
    @if ($label)
        <label class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-2 block">
            {{ $label }}
        </label>
    @endif
    <div class="relative">
        <select
            {{ $attributes->merge([
                'class' =>
                    'w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-2xl px-4 py-3 placeholder:text-neutral-400 focus:ring-2 focus:ring-blue-500 text-neutral-800 dark:text-neutral-200 appearance-none',
            ]) }}>
            {{ $slot }}
        </select>
        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-neutral-400">
            <iconify-icon icon="lucide:chevron-down"></iconify-icon>
        </div>
    </div>
    @error($attributes->get('wire:model') ?? $attributes->get('name'))
        <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span>
    @enderror
</div>
