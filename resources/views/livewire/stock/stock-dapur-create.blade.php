<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" wire:navigate class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-400 hover:text-primary-600 transition-all shadow-sm">
                <iconify-icon icon="lucide:arrow-left" class="text-xl"></iconify-icon>
            </a>
            <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Bahan Dapur' }}</h6>
        </div>
        <x-breadcrumb :title="$title ?? 'Bahan Dapur'" />
    </div>
    <x-toast />
    @php
        $submit = $stockId ? 'update(' . $stockId . ')' : 'simpan';
        $button = $stockId ? 'Update' : 'Simpan';
    @endphp

    <div class="grid grid-cols-12 gap-6">
        <!-- Kolom Kiri: Spesifikasi Item -->
        <div class="col-span-12 lg:col-span-8">
            <div class="card rounded-3xl border-0 shadow-sm dark:bg-neutral-800">
                <div class="card-body p-6">
                    <div class="flex items-center gap-3 mb-8">
                        <div
                            class="w-10 h-10 bg-blue-100 dark:bg-blue-600/20 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="lucide:plus" class="text-xl"></iconify-icon>
                        </div>
                        <h2 class="text-xl font-bold text-neutral-800 dark:text-white">Item Specifications</h2>
                    </div>

                    <form wire:submit.prevent="{{ $submit }}" class="space-y-6">
                        <!-- Nama Bahan -->
                        <x-ui.input label="Nama Bahan (Item Name)" wire:model="nama_bahan"
                            placeholder="e.g. Premium Grade Flour" required />

                        <!-- Stok & HPP -->
                        <div class="grid grid-cols-2 gap-4">
                            <x-ui.input label="Stok Awal (Initial Stock)" type="number" wire:model="stok" suffix="QTY"
                                placeholder="0.00" required />
                            <x-ui.input label="HPP (COGS)" type="number" wire:model="hpp" prefix="Rp"
                                placeholder="0" />
                        </div>

                        <!-- Seleksi Satuan Ikon -->
                        <div>
                            <label class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-4 block">
                                Satuan (Unit Selection)
                            </label>
                            <div class="grid grid-cols-4 gap-4">
                                @forelse($satuans as $sat)
                                    @php
                                        $nama = strtolower($sat->nama_satuan);
                                        $icon = 'ph:hash';
                                        if (str_contains($nama, 'kg') || str_contains($nama, 'gram'))
                                            $icon = 'ph:scales';
                                        elseif (str_contains($nama, 'pcs') || str_contains($nama, 'biji') || str_contains($nama, 'buah'))
                                            $icon = 'ph:package';
                                        elseif (str_contains($nama, 'liter') || str_contains($nama, 'ml'))
                                            $icon = 'ph:drop';
                                        elseif (str_contains($nama, 'box') || str_contains($nama, 'dus'))
                                            $icon = 'ph:package-fill';
                                    @endphp
                                    <button type="button" wire:click="$set('satuan_id', {{ $sat->id }})"
                                        class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 transition-all duration-200 gap-2 
                                            {{ $satuan_id == $sat->id
                                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                                : 'border-transparent bg-neutral-50 dark:bg-neutral-900 hover:bg-neutral-100 dark:hover:bg-neutral-800' }}">

                                        <iconify-icon icon="{{ $icon }}"
                                            class="text-3xl {{ $satuan_id == $sat->id ? 'text-blue-600' : 'text-neutral-400' }}">
                                        </iconify-icon>

                                        <span
                                            class="text-xs font-bold uppercase {{ $satuan_id == $sat->id ? 'text-blue-600' : 'text-neutral-500' }}">
                                            {{ $sat->nama_satuan }}
                                        </span>
                                    </button>
                                @empty
                                    <div class="col-span-full py-4 text-center text-neutral-400 text-sm italic">
                                        Belum ada satuan...
                                    </div>
                                @endforelse
                            </div>
                            @error('satuan_id') <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Tombol Simpan -->
                        <div class="flex justify-end pt-4">
                            <x-ui.button type="submit" class="min-w-[150px]">
                                {{ $button }}
                            </x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Manajemen Satuan -->
        <div class="col-span-12 lg:col-span-4 space-y-6">
            <!-- Tambah Satuan -->
            <div class="card rounded-3xl border-0 shadow-sm dark:bg-neutral-800">
                <div class="card-body p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div
                            class="w-10 h-10 bg-purple-100 dark:bg-purple-600/20 text-purple-600 dark:text-purple-400 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="lucide:layout-grid" class="text-xl"></iconify-icon>
                        </div>
                        <h2 class="text-base font-bold text-neutral-800 dark:text-white">Tambah Satuan Baru</h2>
                    </div>

                    <form wire:submit.prevent="saveSatuan" class="space-y-4">
                        <x-ui.input label="UNIT NAME" wire:model="newSatuan" placeholder="e.g. Pack, Bottle, Roll"
                            required />
                        <x-ui.button type="submit" color="purple" class="w-full">
                            Add Unit
                        </x-ui.button>
                    </form>
                </div>
            </div>

            <!-- Existing Units List -->
            <div class="card rounded-3xl border-0 shadow-sm dark:bg-neutral-800">
                <div class="card-body p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 bg-blue-100 dark:bg-blue-600/20 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center">
                                <iconify-icon icon="lucide:list" class="text-xl"></iconify-icon>
                            </div>
                            <h3 class="text-base font-bold text-neutral-800 dark:text-white">Existing Units</h3>
                        </div>
                        <span
                            class="px-3 py-1 bg-neutral-100 dark:bg-neutral-900 text-[10px] font-bold text-neutral-500 rounded-full border border-neutral-200 dark:border-neutral-700 shadow-sm">
                            {{ count($satuans) }} Active
                        </span>
                    </div>

                    <div class="space-y-3">
                        @foreach($satuans as $index => $sat)
                            <div
                                class="flex items-center justify-between p-3 bg-neutral-50 dark:bg-neutral-900/50 rounded-2xl border border-neutral-100 dark:border-neutral-700 shadow-sm">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 bg-white dark:bg-neutral-800 text-blue-500 font-bold text-xs rounded-xl flex items-center justify-center border border-neutral-200 dark:border-neutral-700">
                                        {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                    </div>
                                    <span class="font-bold text-sm text-neutral-700 dark:text-neutral-300">
                                        {{ $sat->nama_satuan }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button wire:click="editSatuan({{ $sat->id }})"
                                        class="w-7 h-7 bg-success-100 dark:bg-success-600/20 text-success-600 dark:text-success-400 rounded-full inline-flex items-center justify-center hover:scale-110 transition-transform">
                                        <iconify-icon icon="lucide:edit-2" class="text-xs"></iconify-icon>
                                    </button>
                                    <button @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ $sat->id }} })"
                                        class="w-7 h-7 bg-danger-100 dark:bg-danger-600/20 text-danger-600 dark:text-danger-400 rounded-full inline-flex items-center justify-center hover:scale-110 transition-transform">
                                        <iconify-icon icon="mingcute:delete-2-line" class="text-xs"></iconify-icon>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals (Tetap Ada) -->
    <x-mdal name="edit-satuan">
        <div class="px-6 py-4">
            <h3 class="font-semibold text-lg mb-4">Edit Satuan</h3>
            <form wire:submit.prevent="updateSatuan" class="space-y-4">
                <div>
                    <label class="form-label">Nama Satuan</label>
                    <input type="text" wire:model="editSatuanNama" class="form-control"
                        placeholder="Contoh: Liter, Kg, Lusin" required>
                    @error('editSatuanNama')
                        <span class="text-danger-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-neutral-200 dark:border-neutral-700">
                    <button type="button" x-on:click="modalIsOpen = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-100 dark:bg-neutral-700 dark:text-gray-200 dark:border-neutral-600 dark:hover:bg-neutral-600">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700">
                        Update Satuan
                    </button>
                </div>
            </form>
        </div>
    </x-mdal>

    <x-mdl>
        <div class="px-6 py-2 text-center">
            <h3 class="font-semibold text-lg">Hapus Satuan Ini?</h3>
            <p class="text-neutral-500 text-sm mt-1">Data yang dihapus tidak dapat dikembalikan.</p>
        </div>
        <div class="flex justify-center gap-3 border-t border-neutral-200 p-4 dark:border-neutral-700">
            <button x-on:click="modalIsOpen = false"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-neutral-600 dark:bg-neutral-700 dark:text-gray-200 dark:hover:bg-neutral-600">
                Batal
            </button>
            <button x-on:click="$wire.deleteSatuan(selectedId); modalIsOpen = false"
                class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600">
                Hapus
            </button>
        </div>
    </x-mdl>
</div>