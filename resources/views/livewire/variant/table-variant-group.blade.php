<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Grup Varian' }}</h6>
        <x-breadcrumb :title="$title ?? 'Grup Varian'" />
    </div>

    <div
        class="p-6 bg-white dark:bg-neutral-800 rounded-3xl shadow-sm border border-neutral-100 dark:border-neutral-700">
        <x-toast />
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300">
                        <iconify-icon icon="lucide:layers-3" class="text-base"></iconify-icon>
                    </span>
                    <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Manajemen Grup Varian</h1>
                </div>
                <p class="text-sm text-slate-500 dark:text-slate-400">Kelola pilihan tambahan dan harga varian untuk menu Anda</p>
            </div>
            <x-ui.button wire:click="openCreateModal" class="shrink-0">
                <span class="inline-flex items-center gap-2">
                    <iconify-icon icon="lucide:plus" class="text-base"></iconify-icon>
                    Grup Baru
                </span>
            </x-ui.button>
        </div>

        <div class="space-y-4">
            <div
                class="rounded-2xl border border-neutral-100 bg-neutral-50/70 p-4 dark:border-neutral-700 dark:bg-neutral-900/30 sm:p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Daftar grup varian</p>
                        <p class="text-xs text-slate-400 dark:text-slate-500">Atur opsi seperti topping, level gula, dan tambahan lainnya.</p>
                    </div>
                    <x-ui.input wire:model.live="search" placeholder="Cari grup..." class="w-full sm:max-w-xs" />
                </div>
            </div>

            <div class="grid grid-cols-1 items-start gap-3 lg:grid-cols-2 2xl:grid-cols-3">
                @forelse ($groups as $group)
                    <article wire:key="variant-group-{{ $group->id }}" x-data="{ expanded: false }"
                        class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm transition hover:border-indigo-200 hover:shadow-md dark:border-slate-700 dark:bg-slate-900/70 dark:hover:border-indigo-400/40">
                        <div class="border-b border-slate-200 p-4 dark:border-slate-700 sm:p-5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300">
                                        <iconify-icon icon="lucide:layers-3" class="text-lg"></iconify-icon>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="mb-0.5 text-[9px] font-black uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">Grup varian</p>
                                        <h2 class="truncate text-base font-black text-slate-800 dark:text-white">{{ $group->nama_group }}</h2>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <button type="button" @click="expanded = !expanded" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-slate-800 dark:hover:text-indigo-300" :title="expanded ? 'Sembunyikan opsi' : 'Tampilkan opsi'">
                                        <iconify-icon icon="lucide:chevron-down" class="text-base transition-transform duration-200" :class="expanded ? 'rotate-180' : ''"></iconify-icon>
                                    </button>
                                    <button type="button" wire:click="edit({{ $group->id }})" class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-500" title="Edit grup">
                                        <iconify-icon icon="lucide:pencil" class="text-[13px]"></iconify-icon>
                                    </button>
                                    <button type="button" wire:click="delete({{ $group->id }})" wire:confirm="Yakin ingin menghapus grup ini?" class="flex h-8 w-8 items-center justify-center rounded-lg border border-rose-300/40 bg-rose-500/10 text-rose-600 transition hover:bg-rose-500 hover:text-white dark:text-rose-300" title="Hapus grup">
                                        <iconify-icon icon="lucide:trash-2" class="text-[13px]"></iconify-icon>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-[9px] font-black uppercase tracking-wider {{ $group->selection_type === 'single' ? 'border-blue-500/50 bg-blue-600 text-white dark:bg-blue-500' : 'border-purple-500/50 bg-purple-600 text-white dark:bg-purple-500' }}">
                                    <iconify-icon icon="{{ $group->selection_type === 'single' ? 'lucide:circle-dot' : 'lucide:check-check' }}"></iconify-icon>
                                    {{ $group->selection_type === 'single' ? 'Pilih satu' : 'Pilih banyak' }}
                                </span>
                                @if($group->is_required)
                                    <span class="inline-flex items-center gap-1 rounded-lg border border-emerald-400/50 bg-emerald-500 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-white"><iconify-icon icon="lucide:check-circle"></iconify-icon> Wajib</span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-100 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-slate-600 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200">Opsional</span>
                                @endif
                                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ $group->options_count }} pilihan</span>
                            </div>
                        </div>

                        <div x-show="expanded" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="p-3 sm:p-4">
                            <div class="mb-2 flex items-center justify-between gap-3 px-1">
                                <div>
                                    <p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Pilihan tersedia</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400 dark:text-slate-500">Harga tambahan dan bahan</p>
                                </div>
                                <span class="rounded-lg bg-slate-200 px-2 py-1 text-[9px] font-black text-slate-700 dark:bg-slate-700 dark:text-slate-200">{{ $group->options_count }} opsi</span>
                            </div>

                            <div class="grid grid-cols-1 gap-3">
                                @foreach ($group->options as $option)
                                    @php
                                        $variantPrices = $option->variantPrices->pluck('extra_price')->map(fn ($price) => (int) $price);
                                        if ($variantPrices->isEmpty()) {
                                            $priceLabel = '+ Rp 0';
                                        } elseif ($variantPrices->unique()->count() === 1) {
                                            $priceLabel = '+ Rp ' . number_format($variantPrices->first(), 0, ',', '.');
                                        } else {
                                            $priceLabel = 'Rp ' . number_format($variantPrices->min(), 0, ',', '.') . ' - Rp ' . number_format($variantPrices->max(), 0, ',', '.');
                                        }
                                    @endphp
                                    <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50/70 p-3 transition hover:border-indigo-200 hover:bg-indigo-50/40 dark:border-slate-700 dark:bg-slate-950/40 dark:hover:border-indigo-400/30 dark:hover:bg-indigo-500/5">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-indigo-500 shadow-sm dark:bg-slate-800 dark:text-indigo-300"><span class="text-[11px] font-black">{{ $loop->iteration }}</span></div>
                                            <div class="min-w-0">
                                                <p class="mb-0.5 text-[8px] font-black uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500">Pilihan</p>
                                                <p class="truncate text-[13px] font-bold text-slate-700 dark:text-slate-200">{{ $option->nama_opsi }}</p>
                                                <span class="mt-1 inline-flex rounded-md bg-emerald-500 px-1.5 py-0.5 text-[9px] font-black text-white">{{ $priceLabel }}</span>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400"><iconify-icon icon="lucide:package" class="mr-1 align-[-2px]"></iconify-icon>{{ $option->ingredients_count ?? 0 }} bahan</span>
                                            <a href="{{ route('variant-option.ingredients', base64_encode($option->id)) }}" wire:navigate class="inline-flex items-center gap-1 rounded-lg border border-amber-400/60 bg-amber-500/10 px-2.5 py-1.5 text-[10px] font-black text-amber-700 transition hover:bg-amber-500 hover:text-white dark:text-amber-300" title="Atur resep bahan">
                                                <iconify-icon icon="lucide:flask-conical" class="text-xs"></iconify-icon>
                                                <span>Resep</span>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-3xl border border-dashed border-slate-300 bg-slate-50/70 px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900/40">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500"><iconify-icon icon="lucide:layers-3" class="text-2xl"></iconify-icon></div>
                        <p class="font-bold text-slate-700 dark:text-slate-200">Belum ada grup varian</p>
                        <p class="mt-1 text-sm text-slate-400 dark:text-slate-500">Buat grup seperti Topping atau Sugar untuk mulai mengatur pilihan menu.</p>
                    </div>
                @endforelse
            </div>

            <div class="pt-1">
                {{ $groups->links() }}
            </div>
        </div>

        {{-- MODAL CRUD --}}
        @if($showModal)
            <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 p-3 backdrop-blur-sm sm:p-5">
                <div
                    class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900 sm:rounded-3xl">

                    {{-- Modal Header --}}
                    <div
                        class="flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-900 sm:px-6">
                        <div>
                            <h3 class="text-xl font-black text-slate-800 dark:text-white">
                                {{ $isEdit ? 'Edit' : 'Tambah' }} Grup Varian
                            </h3>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Atur pilihan dan harga tambahan untuk produk.</p>
                        </div>
                        <button wire:click="$set('showModal', false)"
                            class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-white">
                            <iconify-icon icon="lucide:x" class="text-xl"></iconify-icon>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="custom-scrollbar flex-1 space-y-5 overflow-y-auto bg-slate-50/70 p-5 dark:bg-slate-950/30 sm:p-6">
                        {{-- Form Fields --}}
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <x-ui.input label="Nama Grup" wire:model="nama_group" placeholder="Contoh: Ukuran, Warna" />

                            <x-ui.select label="Tipe Seleksi" wire:model="selection_type">
                                <option value="single">Pilih satu (Single)</option>
                                <option value="multiple">Pilih banyak (Multiple)</option>
                            </x-ui.select>
                        </div>

                        {{-- Required Checkbox --}}
                        <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 dark:border-slate-700 dark:bg-slate-900">
                            <input type="checkbox" wire:model="is_required" id="is_req"
                                class="h-5 w-5 cursor-pointer rounded-md border-slate-300 text-blue-600 transition-all focus:ring-blue-500 dark:border-slate-600">
                            <label for="is_req"
                                class="cursor-pointer text-xs font-bold text-slate-700 dark:text-slate-200">
                                Wajib dipilih oleh pelanggan?
                            </label>
                        </div>

                        {{-- Options Section --}}
                        <div class="space-y-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h4 class="text-xs font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Opsi pilihan</h4>
                                    <p class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">Tambahkan pilihan dan atur harga per channel.</p>
                                </div>
                                <button wire:click="addOption"
                                    class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-3 py-2 text-xs font-black text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-500">
                                    <iconify-icon icon="lucide:plus" class="text-sm"></iconify-icon>
                                    Tambah
                                </button>
                            </div>

                            <div class="space-y-4">
                                @foreach($options as $index => $option)
                                    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900 animate-in fade-in slide-in-from-top-2 duration-300">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex-1">
                                                <x-ui.input wire:model="options.{{ $index }}.nama_opsi" placeholder="Nama Opsi" />
                                            </div>
                                            <button wire:click="removeOption({{ $index }})"
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-300" title="Hapus opsi">
                                                <iconify-icon icon="lucide:trash-2" class="text-base"></iconify-icon>
                                            </button>
                                        </div>
                                        
                                        {{-- Matrix Pricing --}}
                                        <div class="space-y-3">
                                            <h5 class="border-b border-slate-200 pb-2 text-[10px] font-black uppercase tracking-[0.16em] text-slate-500 dark:border-slate-700 dark:text-slate-400">Harga tambahan per tier & channel</h5>
                                            @foreach($tiers as $tier)
                                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                                                    <div class="mb-2 text-xs font-black text-slate-700 dark:text-slate-200">{{ $tier->nama_tier }}</div>
                                                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                                                        @foreach($salesChannels as $channel)
                                                            <x-ui.input type="number" label="{{ $channel->nama_channel }}" wire:model.lazy="options.{{ $index }}.prices.tier_{{ $tier->id }}.channel_{{ $channel->id }}" prefix="Rp" placeholder="0" />
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div
                        class="flex items-center justify-end gap-3 border-t border-slate-200 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-900 sm:px-6">
                        <button wire:click="$set('showModal', false)"
                            class="rounded-xl px-4 py-2.5 text-xs font-black text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
                            Batal
                        </button>
                        <x-ui.button wire:click="save" class="!rounded-xl !px-5 !py-2.5 !text-xs">
                            Simpan Grup
                        </x-ui.button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
