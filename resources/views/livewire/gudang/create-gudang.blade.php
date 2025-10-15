<div>
    <x-toast />
    @php
        $submit = $gudangId ? 'update(' . $gudangId . ')' : 'simpan';
        $button = $gudangId ? 'Update' : 'Simpan';
    @endphp
    <form wire:submit.prevent="{{ $submit }}" class="grid grid-cols-12 gap-4">

        {{-- Nama Bahan --}}
        <div class="md:col-span-6 col-span-12">
            <div x-data="{ open: false, query: '', results: @entangle('daftarBahan'), selected: '' }" class="relative">
                <label class="form-label">Nama Bahan</label>
                <input type="text" x-model="query" @input.debounce.300ms="$wire.searchBahan(query); open = true"
                    @click="open = true" @keydown.escape="open = false" @blur="setTimeout(() => open = false, 150)"
                    wire:model.defer="nama_bahan"
                    class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                    placeholder="Contoh: Gula, Sirup, Kopi" required>

                <template x-if="open && results.length > 0">
                    <ul
                        class="absolute z-50 w-full bg-white dark:bg-neutral-700 border border-neutral-300 dark:border-neutral-600 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto">
                        <template x-for="item in results" :key="item">
                            <li @click="query = item; $wire.set('nama_bahan', item); open = false"
                                class="px-3 py-2 cursor-pointer hover:bg-neutral-100 dark:hover:bg-neutral-600"
                                x-text="item"></li>
                        </template>
                    </ul>
                </template>
            </div>

        </div>

        {{-- Tipe Transaksi --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Tipe Transaksi</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model="tipe" value="masuk">
                    <span>Masuk</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model="tipe" value="keluar">
                    <span>Keluar</span>
                </label>
            </div>
        </div>

        {{-- Jumlah --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Jumlah</label>
            <input type="number" wire:model="jumlah_masuk"
                class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                placeholder="Contoh: 5" required>
        </div>

        {{-- Satuan --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Satuan</label>
            <select wire:model="satuan"
                class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                required>
                <option value="">Pilih satuan</option>
                <option value="pcs">PCS</option>
                <option value="liter">Liter</option>
                <option value="kg">Kilogram</option>
                <option value="gram">Gram</option>
                <option value="ml">Mililiter</option>
            </select>
        </div>

        {{-- Harga Satuan --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Harga Satuan (Rp)</label>
            <input type="number" wire:model="harga_satuan"
                class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                placeholder="Contoh: 15000">
        </div>

        {{-- Minimum Stok --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Minimum Stok</label>
            <input type="number" wire:model="minimum_stok"
                class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                placeholder="Contoh: 3">
        </div>

        {{-- Keterangan --}}
        <div class="md:col-span-12 col-span-12">
            <label class="form-label">Keterangan</label>
            <input type="text" wire:model="keterangan"
                class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                placeholder="Contoh: Stok awal / Penggunaan harian">
        </div>

        {{-- Submit --}}
        <div class="col-span-12">
            <button class="btn btn-primary-600" type="submit">{{ $button }} Stok Gudang</button>
        </div>
    </form>

</div>
