<div>
    <x-toast />
    @php
        $submit = $discountId ? 'update(' . $discountId . ')' : 'simpan';
        $button = $discountId ? 'Update' : 'Simpan';
    @endphp

    <form wire:submit.prevent="{{ $submit }}" class="grid grid-cols-12 gap-4">
        {{-- Nama Diskon --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Nama Diskon</label>
            <input type="text" wire:model="nama_diskon" class="form-control" placeholder="Contoh: Promo Akhir Pekan"
                required>
        </div>

        {{-- Jenis Diskon --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Jenis Diskon</label>
            <select wire:model="jenis_diskon"
                class="form-control rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer"
                required>
                <option value="" class="bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-200">Pilih jenis</option>
                <option value="persentase" class="bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-200">Persentase (%)</option>
                <option value="nominal" class="bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-200">Nominal (Rp)</option>
            </select>
        </div>
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Type Diskon</label>
            <select wire:model="type"
                class="form-control rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-500 transition cursor-pointer"
                required>
                <option value="" class="bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-200">Pilih Type
                </option>
                <option value="general" class="bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-200">General
                </option>
                <option value="private" class="bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-200">Private
                </option>
            </select>
        </div>
        {{-- Nilai Diskon --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Nilai Diskon</label>
            <input type="number" wire:model="nilai_diskon" class="form-control" placeholder="Contoh: 10 atau 20000"
                required>
        </div>

        {{-- Minimum Transaksi --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Minimum Transaksi</label>
            <input type="number" wire:model="minimum_transaksi" class="form-control" placeholder="Contoh: 50000">
        </div>

        {{-- Maksimum Diskon (jika persentase) --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Maksimum Diskon</label>
            <input type="number" wire:model="maksimum_diskon" class="form-control" placeholder="Contoh: 25000">
        </div>

        {{-- Kode Diskon (opsional) --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Kode Diskon (opsional)</label>
            <input type="text" wire:model="kode_diskon" class="form-control" placeholder="Contoh: PROMO10">
        </div>

        {{-- Tanggal Mulai --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Tanggal Mulai</label>
            <input type="date" wire:model="tanggal_mulai" class="form-control">
        </div>

        {{-- Tanggal Akhir --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Tanggal Akhir</label>
            <input type="date" wire:model="tanggal_akhir" class="form-control">
        </div>
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Limit</label>
            <input type="number" wire:model="limit" class="form-control">
        </div>

        {{-- Status Aktif --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Status</label>
            <select wire:model="is_active" class="form-control">
                <option value="1" class="bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-200">Aktif</option>
                <option value="0" class="bg-white text-slate-800 dark:bg-slate-800 dark:text-slate-200">Tidak Aktif</option>
            </select>
        </div>

        {{-- Submit --}}
        <div class="col-span-12">
            <button class="btn btn-primary-600" type="submit">{{ $button }} Diskon</button>
        </div>
    </form>

</div>
