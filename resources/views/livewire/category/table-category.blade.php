<div>

    <div class="table-responsive mt-2">
        <table class="table basic-border-table mb-0">
            <thead>
                <tr>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">#</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">Nama Category</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">Status</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">Create</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($category as $item)
                    <tr>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            <a href="javascript:void(0)" class="text-primary-600">{{ $item->id }}</a>
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            {{ $item->nama }}
                        </td>

                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            <span
                                class=" bg-danger-100 dark:bg-blue-600/25 text-danger-600 dark:text-danger-400 px-8 py-1.5 rounded-full font-medium text-sm">{{ $item->is_active == 1 ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            {{ $item->created_at->format('d M Y') }}</td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            <a href="javascript:void(0)"
                                class="w-8 h-8 bg-primary-50 dark:bg-primary-600/10 text-primary-600 dark:text-primary-400 rounded-full inline-flex items-center justify-center">
                                <iconify-icon icon="iconamoon:eye-light"></iconify-icon>
                            </a>
                            <a href="/category/{{ $item->id }}/edit" wire:navigate
                                class="w-8 h-8 bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 rounded-full inline-flex items-center justify-center">
                                <iconify-icon icon="lucide:edit"></iconify-icon>
                            </a>
                            <a href="javascript:void(0)"
                                class="w-8 h-8 bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-400 rounded-full inline-flex items-center justify-center">
                                <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">No categories found.</td>
                    </tr>
                @endforelse


            </tbody>
        </table>
    </div>

</div>
