@props(['title' => null])
<ul class="flex items-center gap-[6px]">
    <li class="font-medium">
        <a href="/dashboard" class="flex items-center gap-2 hover:text-primary-600 dark:text-white">
            <iconify-icon icon="solar:home-smile-angle-outline" class="icon text-lg"></iconify-icon>
            Dashboard
        </a>
    </li>
    <li class="dark:text-white">-</li>
    <li class="font-medium dark:text-white"><?php echo $title; ?></li>
</ul>
