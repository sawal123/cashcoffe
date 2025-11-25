@props([
    'label' => '',
    'placeholder' => '',
    'message' => null,
])

<div class="flex justify-between mt-2 gap-4 items-center">
    <div class="w-full">
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-200 mb-1">
            {{ $label }}
        </label>

        <div class="relative">
            <input 
                {{ $attributes }}
                type="text" 
                placeholder="{{ $placeholder }}"
                class="w-full rounded-lg border border-slate-300 dark:border-slate-700 
                       bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100
                       px-3 py-2 text-sm focus:outline-none focus:ring-2 
                       focus:ring-blue-500/40 focus:border-blue-500 transition"
            >
        </div>

        @if ($message)
            <p class="text-xs text-slate-500 dark:text-slate-400 italic mt-1">
                {{ $message }}
            </p>
        @endif
    </div>
</div>
