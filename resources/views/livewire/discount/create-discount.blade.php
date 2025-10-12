<div>
    <x-toast />
    @php
        $submit = $discountId ? 'update(' . $discountId . ')' : 'simpan';
        $button = $discountId ? 'Update' : 'Simpan';
    @endphp

    <form wire:submit.prevent="{{ $submit }}" class="grid grid-cols-12 gap-4" >
        {{-- Nama Diskon --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Nama Diskon</label>
            <input type="text" wire:model="nama_diskon" class="form-control" placeholder="Contoh: Promo Akhir Pekan"
                required>
        </div>

        {{-- Jenis Diskon --}}
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Jenis Diskon</label>
            <select wire:model="jenis_diskon" class="form-control" required>
                <option value="">Pilih jenis</option>
                <option value="persentase">Persentase (%)</option>
                <option value="nominal">Nominal (Rp)</option>
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
                <option value="1">Aktif</option>
                <option value="0">Tidak Aktif</option>
            </select>
        </div>

        {{-- Submit --}}
        <div class="col-span-12">
            <button class="btn btn-primary-600" type="submit">{{ $button }} Diskon</button>
        </div>
    </form>

</div>
