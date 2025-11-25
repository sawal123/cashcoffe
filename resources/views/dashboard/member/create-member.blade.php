<x-app-layout>
    <div class="flex justify-between items-center mb-3">
        <a href="/{{ $url }}" wire:navigate
            class="inline-flex  items-center gap-2 bg-purple-600 text-white hover:bg-purple-700 hover:text-white rounded-lg px-3.5 py-2 text-sm">
            <iconify-icon icon="solar:round-alt-arrow-left-outline" class="text-lg"></iconify-icon>
            <span>Kembali</span>
        </a>
        <x-breadcrumb title="{{ $title }}" />
    </div>
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        <div class="md:col-span-12 2xl:col-span-12">
            <div class="card rounded-lg border-0">
                <div class="grid grid-cols-1 2xl:grid-cols-12">
                    <div class="xl:col-span-12 2xl:col-span-12">
                        <div class="card-body p-6">
                            <div class="grid grid-cols-12 gap-5">
                                <div class="col-span-12">
                                    <div class="card border-0">
                                        <div class="card-body">
                                            <livewire:member.create-member :member-id="$memberId" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
