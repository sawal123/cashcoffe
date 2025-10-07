<div>
    <x-toast />
    <div class="flex gap-2">
        <x-droppage perPage="{{ $perPage }}" />
        <div class="sm:w-[300px] w-ful">
            <x-input wire:model.live="search" place="Cari..." />
        </div>



    </div>

    <div class="table-responsive mt-2">
        <table class="table basic-border-table mb-2">
            <thead>
                <tr>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">#</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">No Meja</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">Status</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($category as $index=>$item)
                    <tr>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            <span>{{ ($category->currentPage() - 1) * $category->perPage() + $loop->iteration }}</span>
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            <span class="">{{ $item->nama }}</span>
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            <span
                                class=" bg-danger-100 dark:bg-blue-600/25 text-danger-600 dark:text-danger-400 px-8 py-1.5 rounded-full font-medium text-sm">
                                {{ $item->is_active == 1 ? 'Active' : 'Inactive' }}
                            </span>

                        </td>



                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            <a href="/category/{{ base64_encode($item->id) }}/edit" wire:navigate
                                class="w-8 h-8 bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 rounded-full inline-flex items-center justify-center">
                                <iconify-icon icon="lucide:edit"></iconify-icon>
                            </a>
                            <a href="javascript:void(0)"
                                @click="$dispatch('open-modal', {name : 'confirm-delete', id : '{{ base64_encode($item->id) }}'})"
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
        {{ $category->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}


    </div>


    <x-mdl>
        <div class="px-6 py-2 text-center ">
            <h3 class="font-semibold text-lg">Hapus Category Ini?</h3>
        </div>
        <div class="flex justify-center gap-3 border-t border-neutral-200 p-4 dark:border-neutral-700">
            <button x-on:click="modalIsOpen = false"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-200 dark:hover:bg-neutral-600">
                Cancel
            </button>
            <button x-on:click="$wire.deleteCategory(selectedId); modalIsOpen = false"
                class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600">
                Delete
            </button>
        </div>
    </x-mdl>

</div>
