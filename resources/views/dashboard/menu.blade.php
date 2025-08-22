<x-app-layout>
    <span class="mb-3 font-bold text-2xl">Menu</span>
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        <div class="md:col-span-12 2xl:col-span-12">
            <div class="card rounded-lg border-0">
                <div class="grid grid-cols-1 2xl:grid-cols-12">
                    <div class="xl:col-span-12 2xl:col-span-12">
                        <div class="card-body p-6">
                           <x-a url='/menu/create' active='blue' wire:navigate>+ Tambah Menu</x-a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
