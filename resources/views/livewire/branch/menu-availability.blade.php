<div>
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            @if(auth()->user()->hasRole('superadmin'))
                <div class="w-64">
                    <label class="form-label text-sm font-semibold mb-1">Pilih Cabang</label>
                    <select wire:model.live="selectedBranchId"
                        class="form-select rounded-lg border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-sm">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->nama_cabang }} ({{ $b->priceTier?->nama_tier ?? 'No Tier' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="w-64">
                <label class="form-label text-sm font-semibold mb-1">Cari Menu</label>
                <div class="relative">
                    <input type="text" wire:model.live="search"
                        class="form-control pl-10 rounded-lg border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 text-sm"
                        placeholder="Ketik nama menu...">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400">
                        <iconify-icon icon="lucide:search"></iconify-icon>
                    </span>
                </div>
            </div>
        </div>

        <div
            class="bg-primary-50 dark:bg-primary-900/20 p-3 rounded-lg border border-primary-100 dark:border-primary-800/50 max-w-md">
            <p class="text-xs text-primary-700 dark:text-primary-300 flex gap-2">
                <iconify-icon icon="lucide:info" class="text-lg flex-shrink-0"></iconify-icon>
                <span>Menu yang muncul di sini adalah yang sudah diberi <b>Harga</b> oleh Pusat untuk Tier cabang ini.
                    Cabang cukup mengaktifkan/matikan sesuai ketersediaan.</span>
            </p>
        </div>
    </div>

    <div class="space-y-8">
        @foreach($categories as $category)
            @if($category->menus->count() > 0)
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="h-px bg-neutral-200 dark:bg-neutral-700 flex-grow"></div>
                        <h3 class="text-sm font-bold text-neutral-500 uppercase tracking-wider">{{ $category->nama_kategori }}
                        </h3>
                        <div class="h-px bg-neutral-200 dark:bg-neutral-700 flex-grow"></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($category->menus as $menu)
                            <div
                                class="p-4 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800/50 flex items-center justify-between group hover:border-primary-300 dark:hover:border-primary-700 transition-all">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-12 h-12 rounded-lg bg-white dark:bg-neutral-800 flex items-center justify-center text-xl text-primary-600 shadow-sm border border-neutral-100 dark:border-neutral-700 overflow-hidden">
                                        @if($menu->gambar)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($menu->gambar) }}"
                                                alt="{{ $menu->nama_menu }}" class="w-full h-full object-cover">
                                        @else
                                            <iconify-icon icon="solar:coffee-cup-bold"></iconify-icon>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-neutral-800 dark:text-neutral-200">{{ $menu->nama_menu }}
                                        </p>
                                        <p class="text-xs text-neutral-500">Global ID: #{{ $menu->id }}</p>
                                    </div>
                                </div>

                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:click="toggleAvailability({{ $menu->id }})" class="sr-only peer" {{ $menu->branch_available ? 'checked' : '' }}>
                                    <div
                                        class="w-11 h-6 bg-neutral-300 peer-focus:outline-none rounded-full peer dark:bg-neutral-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-neutral-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-neutral-600 peer-checked:bg-success-500">
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        @if($categories->flatMap->menus->isEmpty())
            <div class="text-center py-12">
                <div
                    class="w-20 h-20 bg-neutral-100 dark:bg-neutral-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <iconify-icon icon="solar:box-search-bold-duotone" class="text-4xl text-neutral-400"></iconify-icon>
                </div>
                <p class="text-neutral-500">Tidak ada menu yang ditemukan atau belum ada harga di Tier cabang ini.</p>
            </div>
        @endif
    </div>
</div>