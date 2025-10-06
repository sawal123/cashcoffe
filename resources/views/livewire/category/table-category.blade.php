<div>
    <x-toast />
    <div class="flex gap-2">
       <x-droppage perPage="{{ $perPage }}" />
        <div class="sm:w-[300px] w-ful">
            <x-input wire:model.live="search" place="Cari..." />
        </div>
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
