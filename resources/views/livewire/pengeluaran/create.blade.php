<div>
    <div>
        <x-toast />

        @php
            $submit = $pengeluaranId ? 'update(' . $pengeluaranId . ')' : 'simpan';
            $button = $pengeluaranId ? 'Update' : 'Simpan';
        @endphp

        <form wire:submit.prevent="{{ $submit }}" class="grid grid-cols-12 gap-4" >

            {{-- Tanggal Pengeluaran --}}
            <div class="md:col-span-6 col-span-12">
                <label class="form-label">Tanggal Pengeluaran</label>
                <input type="date" wire:model="tanggal_pengeluaran"
                    class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2
                bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                    required>
            </div>

            {{-- Kategori Pengeluaran --}}
            <div class="md:col-span-6 col-span-12">
                <label class="form-label">Kategori</label>
                <select wire:model="kategori"
                    class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2
                bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200">
                    <option value="">Pilih kategori</option>
                    <option value="Bahan Baku">Bahan Baku</option>
                    <option value="Gaji Karyawan">Gaji Karyawan</option>
                    <option value="Listrik & Air">Listrik & Air</option>
                    <option value="Kebersihan">Kebersihan</option>
                    <option value="Peralatan">Peralatan</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>

            {{-- Judul / Nama Item --}}
            <div class="md:col-span-6 col-span-12">
                <label class="form-label">Nama Item</label>
                <input type="text" wire:model="title"
                    class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2
                bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                    placeholder="Contoh: Kopi Arabica, Sirup Gula, Listrik Bulan Oktober" required>
            </div>

            {{-- Satuan --}}
            <div class="md:col-span-6 col-span-12">
                <label class="form-label">Satuan</label>
                <select wire:model="satuan"
                    class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2
                bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200">
                    <option value="">Pilih satuan</option>
                    <option value="pcs">PCS</option>
                    <option value="kg">Kilogram</option>
                    <option value="liter">Liter</option>
                    <option value="gram">Gram</option>
                    <option value="ml">Mililiter</option>
                    <option value="paket">Paket</option>
                    <option value="bulan">Bulan</option>
                </select>
            </div>

            {{-- Total Pengeluaran --}}
            <div class="md:col-span-6 col-span-12">
                <label class="form-label">Total (Rp)</label>
                <input type="number" wire:model="total" min="0" step="100"
                    class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2
                bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                    placeholder="Contoh: 150000" required>
            </div>

            {{-- Metode Pembayaran --}}
            <div class="md:col-span-6 col-span-12">
                <label class="form-label">Metode Pembayaran</label>
                <select wire:model="metode_pembayaran"
                    class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2
                bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200">
                    <option value="">Pilih metode</option>
                    <option value="Cash">Cash</option>
                    <option value="Transfer Bank">Transfer Bank</option>
                    <option value="E-Wallet">E-Wallet</option>
                </select>
            </div>

            {{-- Catatan --}}
            <div class="md:col-span-12 col-span-12">
                <label class="form-label">Catatan</label>
                <textarea wire:model="catatan" rows="3"
                    class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2
                bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200"
                    placeholder="Contoh: Pembelian bahan baku minggu pertama"></textarea>
            </div>

            {{-- Bukti Pembayaran --}}
            <div class="md:col-span-12 col-span-12" wire:ignore.self>
                <label class="form-label">Upload Bukti Pembayaran (Opsional)</label>
                <input type="file" wire:model="bukti"
                    class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2
                bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200">

                @if ($bukti && !is_string($bukti))
                    <p class="text-sm text-green-600 mt-1">Bukti berhasil diunggah.</p>
                @elseif (is_string($bukti))
                    <a href="{{ asset('storage/' . $bukti) }}" target="_blank"
                        class="text-blue-600 text-sm mt-1 block">Lihat bukti lama</a>
                @endif
            </div>

            {{-- Submit --}}
            <div class="col-span-12">
                <button class="btn btn-primary-600 w-full md:w-auto" type="submit">
                    {{ $button }} Pengeluaran
                </button>
            </div>
        </form>
    </div>

</div>
