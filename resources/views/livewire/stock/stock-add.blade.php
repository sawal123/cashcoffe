<div>
    <x-toast />
    <form wire:submit.prevent="tambahStok" class="grid grid-cols-12 gap-4">

        <div class="col-span-12">
            {{-- <label class="form-label">Pilih Bahan</label>
            <select wire:model.live="ingredient_id"
                class="form-control form-select w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200">
                <option value="">-- Pilih Bahan --</option>
                @foreach ($ingredients as $bahan)
                    <option value="{{ $bahan->id }}">{{ $bahan->nama_bahan }}</option>
                @endforeach
            </select>
            @if ($current_stok !== null)
                <p class="text-sm mt-2">Stok Saat Ini: <b>{{ number_format($current_stok, 0) }} {{ $current_satuan }}</b>
                </p>
            @endif --}}

            <div class="mb-4" wire:ignore>
                <div x-data x-init="new TomSelect($refs.menuSelect, {
                    allowEmptyOption: true,
                    create: false,
                    sortField: { field: 'text', direction: 'asc' }
                });">
                    <label class="font-semibold mb-1 block">Pilih Bahan</label>

                    <select x-ref="menuSelect" wire:model.live="menu_id" class="">
                        <option value="">Pilih Bahan:</option>
                        @foreach ($ingredients as $bahan)
                            <option value="{{ $bahan->id }}">{{ $bahan->nama_bahan }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($current_stok !== null)
                    <p class="text-sm mt-2">Stok Saat Ini: <b>{{ number_format($current_stok, 0) }}
                            {{ $current_satuan }}</b>
                    </p>
                @endif
            </div>
        </div>





        <div class="col-span-6">
            <label class="form-label">Jumlah</label>
            <input type="number" wire:model="qty" class="form-control" placeholder="Contoh: 50" required>
        </div>

        <div class="col-span-6">
            <label class="form-label">Keterangan</label>
            <input type="text" wire:model="keterangan" class="form-control" placeholder="contoh: Beli dari supplier">
        </div>

        <div class="col-span-12">
            <button class="btn btn-primary-600" type="submit">Tambahkan Stok</button>
        </div>

    </form>


</div>
