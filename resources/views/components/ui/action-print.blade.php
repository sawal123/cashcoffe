@props(['href' => '#', 'tooltip' => 'Print'])

<a {{ $attributes->merge([
    'href' => $href,
    'class' => 'w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all',
    'title' => $tooltip
]) }}>
    <iconify-icon icon="lucide:printer" class="text-xs"></iconify-icon>
</a>
