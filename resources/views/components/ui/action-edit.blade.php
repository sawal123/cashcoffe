@props(['href' => '#', 'tooltip' => 'Edit'])

<a {{ $attributes->merge([
    'href' => $href,
    'class' => 'w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all',
    'title' => $tooltip
]) }}>
    <i class="ri-pencil-line text-xs leading-none"></i>
</a>
