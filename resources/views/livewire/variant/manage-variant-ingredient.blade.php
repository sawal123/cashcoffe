<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" wire:navigate
                class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-400 hover:text-primary-600 transition-all shadow-sm">
                <iconify-icon icon="lucide:arrow-left" class="text-xl"></iconify-icon>
            </a>
            <div>
                <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title }}</h6>
                <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-0.5">
                    Grup: <span class="font-semibold text-neutral-700 dark:text-neutral-300">{{ $variantOption->group->nama_group ?? '-' }}</span>
                    &bull; Extra Harga: <span class="font-semibold text-emerald-600">Rp {{ number_format($variantOption->extra_price, 0, ',', '.') }}</span>
                </p>
            </div>
        </div>
        <x-breadcrumb :title="$title" />
    </div>

    <x-toast />

    <div class="grid grid-cols-12 gap-6">
        {{-- Kolom Kiri: Resep Saat Ini --}}
        <div class="col-span-12 lg:col-span-7">
            <div class="bg-white dark:bg-neutral-800 rounded-3xl border border-neutral-200 dark:border-neutral-700 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-neutral-100 dark:border-neutral-700 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-500">
                        <iconify-icon icon="lucide:flask-conical" class="text-lg"></iconify-icon>
                    </div>
                    <div>
                        <span class="block font-bold text-neutral-900 dark:text-neutral-100 text-sm">Komposisi Resep</span>
                        <span class="text-xs text-neutral-500">Bahan yang terpotong saat varian ini dipilih</span>
                    </div>
                </div>

                @if (count($recipeRows) > 0)
                    <div class="divide-y divide-neutral-50 dark:divide-neutral-700/50">
                        @foreach ($recipeRows as $row)
                            <div class="px-6 py-4 flex items-center gap-4 hover:bg-neutral-50/50 dark:hover:bg-neutral-900/30 transition" wire:key="row-{{ $row['ingredient_id'] }}">
                                <div class="flex-1">
                                    <span class="block font-bold text-neutral-800 dark:text-neutral-200 text-sm">{{ $row['nama_bahan'] }}</span>
                                    <span class="text-xs text-neutral-400">{{ $row['satuan'] }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="number"
                                        value="{{ $row['qty'] }}"
                                        min="0.01"
                                        step="0.01"
                                        class="w-24 text-center text-sm font-bold border border-neutral-200 dark:border-neutral-700 rounded-xl px-3 py-2 bg-neutral-50 dark:bg-neutral-900 text-neutral-800 dark:text-neutral-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                        wire:change="updateQty({{ $row['ingredient_id'] }}, $event.target.value)"
                                    />
                                    <span class="text-xs text-neutral-400 w-10">{{ $row['satuan'] }}</span>
                                    <button
                                        wire:click="removeIngredient({{ $row['ingredient_id'] }})"
                                        wire:confirm="Yakin hapus bahan ini dari resep?"
                                        class="w-8 h-8 flex items-center justify-center rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-500 border border-rose-100 transition">
                                        <iconify-icon icon="lucide:trash-2" class="text-sm"></iconify-icon>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-16 flex flex-col items-center justify-center gap-3 text-neutral-400">
                        <div class="w-14 h-14 rounded-2xl bg-neutral-50 dark:bg-neutral-900/50 flex items-center justify-center border border-neutral-100 dark:border-neutral-700">
                            <iconify-icon icon="lucide:flask-conical" class="text-2xl text-neutral-300"></iconify-icon>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-semibold text-neutral-500">Belum ada bahan di resep ini</p>
                            <p class="text-xs text-neutral-400 mt-0.5">Tambahkan bahan dari panel sebelah kanan</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Kolom Kanan: Tambah Bahan --}}
        <div class="col-span-12 lg:col-span-5">
            <div class="bg-white dark:bg-neutral-800 rounded-3xl border border-neutral-200 dark:border-neutral-700 shadow-sm">
                <div class="px-6 py-4 border-b border-neutral-100 dark:border-neutral-700 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-500">
                        <iconify-icon icon="mingcute:add-circle-line" class="text-lg"></iconify-icon>
                    </div>
                    <div>
                        <span class="block font-bold text-neutral-900 dark:text-neutral-100 text-sm">Tambah Bahan</span>
                        <span class="text-xs text-neutral-500">Pilih bahan dan masukkan jumlahnya</span>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-neutral-600 dark:text-neutral-400 uppercase tracking-wider mb-2">Pilih Bahan</label>
                        <select
                            wire:model="newIngredientId"
                            class="w-full border border-neutral-200 dark:border-neutral-700 rounded-2xl px-4 py-3 text-sm bg-neutral-50 dark:bg-neutral-900 text-neutral-800 dark:text-neutral-200 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="">-- Pilih Bahan Baku --</option>
                            @foreach ($allIngredients as $ing)
                                <option value="{{ $ing->id }}">{{ $ing->nama_bahan }} ({{ $ing->satuan?->nama_satuan ?? '-' }})</option>
                            @endforeach
                        </select>
                        @error('newIngredientId') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-neutral-600 dark:text-neutral-400 uppercase tracking-wider mb-2">Jumlah (Qty)</label>
                        <input
                            type="number"
                            wire:model="newQty"
                            placeholder="Contoh: 10"
                            min="0.01"
                            step="0.01"
                            class="w-full border border-neutral-200 dark:border-neutral-700 rounded-2xl px-4 py-3 text-sm bg-neutral-50 dark:bg-neutral-900 text-neutral-800 dark:text-neutral-200 focus:outline-none focus:ring-2 focus:ring-primary-500"
                        />
                        @error('newQty') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <x-ui.button wire:click="addIngredient" color="primary" class="w-full !justify-center !py-3">
                        <iconify-icon icon="mingcute:add-circle-line" class="mr-2"></iconify-icon>
                        Tambahkan ke Resep
                    </x-ui.button>
                </div>

                {{-- Info Card --}}
                <div class="mx-6 mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-100 dark:border-blue-800/30">
                    <div class="flex gap-3">
                        <iconify-icon icon="lucide:info" class="text-blue-500 text-lg mt-0.5 shrink-0"></iconify-icon>
                        <div class="text-xs text-blue-700 dark:text-blue-300 leading-relaxed">
                            <strong class="block mb-1">Resep Akumulatif</strong>
                            Bahan di sini akan dijumlahkan dengan resep dasar menu saat transaksi selesai.
                            Contoh: jika "Large" = +10g kopi, maka qty × 10g akan dipotong dari stok kopi cabang.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
