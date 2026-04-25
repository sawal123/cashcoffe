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
    <form wire:submit.prevent='{{ $submit }}' class="grid grid-cols-12 gap-4  items-center">
        <div class="md:col-span-6 col-span-12">
            {{-- <label class="form-label">Nama Category</label>
            <input type="text" class="form-control" required wire:model='category'> --}}
            <x-input wire:model="category" label="Nama Category" required  class="w-full" />
        </div>
        @if ($categoryId)
            <div class="md:col-span-6 col-span-12">
                <label class="flex items-center mt-6 cursor-pointer">
                    <input type="checkbox" class="sr-only peer" wire:model='is_active' {{ $is_active ? 'checked' : '' }}>
                    <span
                        class="relative w-11 h-6 bg-gray-400 peer-focus:outline-none rounded-full peer dark:bg-gray-500 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></span>
                    <span
                        class="line-height-1 font-medium ms-3 peer-checked:text-primary-600 text-md text-gray-600 dark:text-gray-300">Is
                        Acitve?</span>
                </label>
            </div>
        @endif
        <div class="col-span-12">
            <button class="btn btn-primary-600" type="submit">{{ $button }}
                Category</button>
        </div>
    </form>

</div>
