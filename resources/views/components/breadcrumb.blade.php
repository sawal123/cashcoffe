@props(['title' => null])
<ul class="flex items-center justify-end gap-[6px] mb-0">
    <li class="font-medium">
        <a href="{{ route('dashboard.index') }}" wire:navigate class="flex items-center gap-2 text-neutral-400 hover:text-primary-600 dark:text-neutral-300">
            <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
            Dashboard
        </a>
    </li>
    <li class="text-neutral-300 dark:text-neutral-600">/</li>
    <li class="font-bold text-neutral-600 dark:text-neutral-100"><?php echo $title; ?></li>
</ul>
