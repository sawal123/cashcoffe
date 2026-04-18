@props(['href' => '#', 'tooltip' => 'Detail'])

<a {{ $attributes->merge([
    'href' => $href,
    'class' => 'w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition-all',
    'title' => $tooltip
]) }}>
    <iconify-icon icon="lucide:eye" class="text-xs"></iconify-icon>
</a>
