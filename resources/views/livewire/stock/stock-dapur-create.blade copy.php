<div>
    <x-toast />
    @php
        $submit = $stockId ? 'update(' . $stockId . ')' : 'simpan';
        $button = $stockId ? 'Update' : 'Simpan';
    @endphp
    <form wire:submit.prevent="{{$submit}}" class="grid grid-cols-12 gap-4">

        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Nama Bahan</label>
            <input type="text" wire:model="nama_bahan" class="form-control" placeholder="Contoh: Telur" required>
        </div>

        <div class="md:col-span-6 col-span-12">
            <label class="form-label">Stok Awal</label>
            <input type="number" wire:model="stok" class="form-control" placeholder="Contoh: 100" required>
        </div>
        <div class="md:col-span-6 col-span-12">
            <label class="form-label">HPP</label>
            <input type="number" wire:model="hpp" class="form-control" placeholder="1000">
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
            <button class="btn btn-primary-600" type="submit">{{$button}} Bahan</button>
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
                                    <div class=" items-center gap-2">
                                        <button wire:click="editSatuan({{ $satuan->id }})"
                                            class="w-8 h-8 bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-400 rounded-full inline-flex items-center justify-center">
                                            <iconify-icon icon="lucide:edit"></iconify-icon>
                                        </button>
                                        <button
                                            @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ $satuan->id }} })"
                                            class="w-8 h-8 bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-400 rounded-full inline-flex items-center justify-center">
                                            <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>



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