<div>
    <x-toast />

    <form wire:submit.prevent="simpan" class="grid grid-cols-12 gap-4">

        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Nama Bahan</label>
            <input type="text" wire:model="nama_bahan" class="form-control" placeholder="Contoh: Telur" required>
        </div>

        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Stok Awal</label>
            <input type="number" wire:model="stok" class="form-control" placeholder="Contoh: 100" required>
        </div>

        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Satuan</label>
            <select wire:model="satuan_id"
                class="form-select w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200">

                <option value="">-- Pilih Satuan --</option>

                @foreach ($satuans as $sat)
                    <option value="{{ $sat->id }}">{{ $sat->nama_satuan }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-span-12">
            <button class="btn btn-primary-600" type="submit">Tambah Bahan</button>
        </div>
    </form>

    <hr class="my-6">

    <div class="card">
        <div class="card-body">
            <form wire:submit.prevent="saveSatuan" class="grid grid-cols-12 gap-4">
                <div class="md:col-span-6 col-span-12">
                    <label class="form-label">Tambah Satuan Baru</label>
                    <input type="text" wire:model="newSatuan" class="form-control"
                        placeholder="Contoh: Liter, Kg, Lusin" required>
                </div>

                <div class="col-span-12">
                    <button class="btn btn-primary-600" type="submit">Tambah Satuan</button>
                </div>
            </form>

            <div class="table responsive mt-6">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Satuan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($satuans as $index => $satuan)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $satuan->nama_satuan }}</td>
                                <td>
                                    <button wire:click="deleteSatuan({{ $satuan->id }})"
                                        class="btn btn-danger-600 btn-sm">Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>


</div>
