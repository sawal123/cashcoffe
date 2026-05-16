@props(['label' => null])

<div>
    @if ($label)
        <label class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-2 block">
            {{ $label }}
        </label>
    @endif
    <div class="relative">
        <textarea {{ $attributes->merge([
            'class' => 'w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-2xl p-4 placeholder:text-neutral-400 focus:ring-2 focus:ring-blue-500 min-h-[100px]',
        ]) }}></textarea>
    </div>
    @error($attributes->get('wire:model') ?? $attributes->get('name'))
        <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span>
    @enderror
</div>
