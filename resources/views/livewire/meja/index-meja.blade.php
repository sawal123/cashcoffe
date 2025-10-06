<div>

    <x-toast />

    <form wire:submit.prevent="simpan" class="w-full">
        <div class="grid grid-cols-12 gap-4 items-end">
            <div class="md:col-span-6 col-span-12">
                <x-input place="Nomor Meja" wire:model="nomeja" type="number" required class="w-full" />
            </div>
            <div class="md:col-span-6 col-span-12">
                <x-input place="Isi Meja" wire:model="isimeja" type="number" required class="w-full" />
            </div>
        </div>
        <div class="flex justify-end">
            <button class="btn btn-primary-600  text-center" type="submit">{{ $button }}</button>
        </div>
    </form>

    {{-- <hr class="my-2"> --}}
    <div class="table-responsive mt-2">
        <table class="table basic-border-table mb-2">
            <thead>
                <tr>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">#</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">No Meja</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">Kapasitas</th>
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($meja as $index=>$item)
                    <tr>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            <span>{{ $index + 1 }}</span>
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            <span class="">{{ $item->nama }}</span>
                        </td>
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            <span class="">{{ $item->kapasitas }}</span>
                        </td>



                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            <button wire:click='edit({{ $item->id }})'
                                class="w-8 h-8 bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 rounded-full inline-flex items-center justify-center">
                                <iconify-icon icon="lucide:edit"></iconify-icon>
                            </button>
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
        <x-mdl>
            <div class="px-6 py-2 text-center ">
                <h3 class="font-semibold text-lg">Hapus Meja Ini?</h3>
            </div>
            <div class="flex justify-center gap-3 border-t border-neutral-200 p-4 dark:border-neutral-700">
                <button x-on:click="modalIsOpen = false"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-200 dark:hover:bg-neutral-600">
                    Cancel
                </button>
                <button x-on:click="$wire.delMeja(selectedId); modalIsOpen = false"
                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600">
                    Delete
                </button>
            </div>
        </x-mdl>
        {{-- {{ $meja->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }} --}}

    </div>

</div>
