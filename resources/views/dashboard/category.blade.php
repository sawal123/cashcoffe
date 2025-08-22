<x-app-layout>
    <span class="mb-3 font-bold text-2xl">Category</span>
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        <div class="md:col-span-12 2xl:col-span-12">
            <div class="card rounded-lg border-0">
                <div class="grid grid-cols-1 2xl:grid-cols-12">
                    <div class="xl:col-span-12 2xl:col-span-12">
                        <div class="card-body p-6">
                           <x-a url='/category/create' active='blue' wire:navigate>+ Tambah Category</x-a>

                           <livewire:category.table-category>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
