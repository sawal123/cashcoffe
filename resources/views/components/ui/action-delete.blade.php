@props(['tooltip' => 'Hapus'])

<button {{ $attributes->merge([
    'type' => 'button',
    'class' => 'w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center hover:bg-rose-600 hover:text-white transition-all',
    'title' => $tooltip
]) }}>
    <i class="ri-delete-bin-line text-xs leading-none"></i>
</button>
