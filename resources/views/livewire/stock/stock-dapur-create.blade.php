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
            <select wire:model="satuan"
                class="form-select w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200">
                <option value="gram">Gram</option>
                <option value="pcs">Pcs</option>
                <option value="butir">Butir</option>
                <option value="bungkus">Bungkus</option>
                <option value="miligram">Miligram</option>
                <option value="pack">Pack</option>
            </select>

           


        </div>

        <div class="col-span-12">
            <button class="btn btn-primary-600" type="submit">Tambah Stock</button>
        </div>

    </form>

</div>
