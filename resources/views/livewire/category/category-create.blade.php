<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" wire:navigate class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-400 hover:text-primary-600 transition-all shadow-sm">
                <iconify-icon icon="lucide:arrow-left" class="text-xl"></iconify-icon>
            </a>
            <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Kategori' }}</h6>
        </div>
        <x-breadcrumb :title="$title ?? 'Kategori'" />
    </div>
    <x-toast />
    @php
        // $categoryId = $categoryId ?? null;
        $submit = $categoryId ? 'update(' . $categoryId . ')' : 'simpan';
        $button = $categoryId ? 'Update' : 'Simpan';
    @endphp
    <div class="bg-white dark:bg-neutral-800 rounded-3xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm max-w-2xl">
        <form wire:submit.prevent='{{ $submit }}' class="space-y-6">
            <div class="space-y-4">
                <x-ui.input 
                    wire:model="category" 
                    label="Nama Kategori" 
                    placeholder="Contoh: Coffee, Non-Coffee, Snack" 
                    required 
                    class="!py-3"
                />

                @if ($categoryId)
                    <div class="flex items-center justify-between p-4 bg-neutral-50 dark:bg-neutral-900/50 rounded-2xl border border-neutral-100 dark:border-neutral-700">
                        <div>
                            <span class="block font-bold text-neutral-800 dark:text-neutral-200 text-sm">Status Kategori</span>
                            <span class="text-xs text-neutral-500">Tentukan apakah kategori ini aktif atau tidak</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" wire:model.live='is_active'>
                            <div class="w-11 h-6 bg-neutral-200 peer-focus:outline-none rounded-full peer dark:bg-neutral-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-neutral-600 peer-checked:bg-emerald-500"></div>
                        </label>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-ui.button type="submit" color="primary" class="!px-8 !py-3">
                    <iconify-icon icon="lucide:save" class="mr-2"></iconify-icon>
                    {{ $button }} Kategori
                </x-ui.button>
                
                <a href="{{ $backUrl }}" wire:navigate class="text-sm font-bold text-neutral-400 hover:text-neutral-600 transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>

</div>
