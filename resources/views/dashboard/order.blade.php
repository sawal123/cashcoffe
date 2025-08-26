<x-app-layout>
    <div class="flex justify-between items-center mb-3">
        <span class="font-bold text-2xl h-9">{{ $title }}</span>
        <x-breadcrumb title="{{ $title }}" />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        <div class="md:col-span-12 2xl:col-span-12">
            <div class="card rounded-lg border-0">
                <div class="grid grid-cols-1 2xl:grid-cols-12">
                    <div class="xl:col-span-12 2xl:col-span-12">
                        <div class="card-body p-6">
                           <div class="flex justify-end">
                             <x-a url='/order/create' active='blue' wire:navigate>+ Pesan Menu</x-a>
                           </div>
                            <livewire:order.table-order />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
