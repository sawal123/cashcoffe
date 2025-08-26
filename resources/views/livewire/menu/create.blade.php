<div>
    <x-toast />
     @php
        // $categoryId = $categoryId ?? null;
        $submit = $menuId ? 'update(' . $menuId . ')' : 'simpan';
        $button = $menuId ? 'Update' : 'Simpan';
    @endphp
    <form wire:submit.prevent="{{ $submit }}" class="grid grid-cols-12 gap-4" enctype="multipart/form-data">
        {{-- Nama Menu --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Nama Menu</label>
            <input type="text" wire:model="nama_menu" class="form-control" placeholder="Masukkan nama menu" required>
        </div>

        {{-- Kategori --}}
        <div class="md:col-span-6 col-span-12">
            <label for="categories_id" class="form-label">Kategori</label>
            <select id="categories_id" wire:model="categories_id"
                class="form-select w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                required>
                <option value="" class="text-neutral-500">-- Pilih Kategori --</option>
                @foreach ($category as $item)
                    <option value="{{ $item->id }}" class="text-neutral-800 dark:text-neutral-200">
                        {{ $item->nama }}
                    </option>
                @endforeach
            </select>
        </div>


        {{-- Harga --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Harga</label>
            <div class="flex">
                <span
                    class="inline-flex items-center px-3 border rounded-e-0 border-e-0 rounded-s-md border-neutral-200 dark:border-neutral-600">
                    Rp
                </span>
                <input type="number" step="0.01" wire:model="harga"
                    class="form-control grow rounded-ss-none rounded-es-none" placeholder="0.00" required>
            </div>
        </div>

        {{-- Status Aktif --}}
        <div class="md:col-span-6 col-span-12">
            <label class="flex items-center md:mt-10 cursor-pointer">
                <input type="checkbox" class="sr-only peer" wire:model="is_active" {{ $is_active ? 'checked' : '' }}>
                <span
                    class="relative w-11 h-6 bg-gray-400 peer-focus:outline-none rounded-full peer dark:bg-gray-500 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></span>
                <span
                    class="line-height-1 font-medium ms-3 peer-checked:text-primary-600 text-md text-gray-600 dark:text-gray-300">Is
                    Acitve?</span>
            </label>
        </div>

        {{-- Deskripsi --}}
        <div class="col-span-12">
            <label class="form-label">Deskripsi</label>
            <textarea wire:model="deskripsi" class="form-control" rows="3" placeholder="Tuliskan deskripsi menu..."></textarea>
        </div>

        {{-- Gambar --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Gambar Menu</label>
            <div class="upload-image-wrapper flex items-center gap-3">
                <label
                    class="upload-file cursor-pointer h-[120px] w-[120px] border input-form-light rounded-lg overflow-hidden border-dashed bg-neutral-50 dark:bg-neutral-600 hover:bg-neutral-200 flex items-center flex-col justify-center gap-1"
                    for="upload-file">
                    <iconify-icon icon="solar:camera-outline" class="text-xl text-secondary-light"></iconify-icon>
                    <span class="font-semibold text-secondary-light">Upload</span>
                    <input id="upload-file" wire:model="gambar" type="file" hidden>
                </label>

                <div wire:ignore.self  {{ !$gambarUrl ? 'hidden' : '' }}
                    class="uploaded-img  relative h-[120px] w-[120px] border input-form-light rounded-lg overflow-hidden border-dashed bg-neutral-50 dark:bg-neutral-600">
                    <button type="button"
                        class="uploaded-img__remove absolute top-0 end-0 z-1 text-2xxl line-height-1 me-1 mt-2 flex">
                        <iconify-icon icon="radix-icons:cross-2" class="text-xl text-danger-600"></iconify-icon>
                    </button>

                    <img id="uploaded-img__preview" class="w-full h-full object-fit-cover" wire:ignore.self
                        src="{{ $gambarUrl ?? asset('assets/images/user.png') }}" alt="image">

                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="col-span-12">
            <button class="btn btn-primary-600" type="submit">{{ $button }} Menu</button>
        </div>
    </form>

</div>
