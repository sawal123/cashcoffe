<div>
    <x-toast />
    <form wire:submit.prevent="{{ $stockId ? 'updateStok' : 'tambahStok' }}" class="grid grid-cols-12 gap-4">


        <div class="col-span-12">


            <div class="mb-4" wire:ignore>
                <div x-data x-init="const ts = new TomSelect($refs.menuSelect, {
                    allowEmptyOption: true,
                    create: false,
                    sortField: { field: 'text', direction: 'asc' }
                });
                
                // Set TomSelect value saat EDIT
                $nextTick(() => {
                    @this.ingredient_id && ts.setValue(@this.ingredient_id);
                });
                
                // Sinkronisasi TomSelect -> Livewire
                ts.on('change', function(value) {
                    @this.set('ingredient_id', value);
                });">
                    <label class="font-semibold mb-1 block">Pilih Bahan</label>

                    <select x-ref="menuSelect" class="">
                        <option value="">Pilih Bahan:</option>
                        @foreach ($ingredients as $bahan)
                            <option value="{{ $bahan->id }}">{{ $bahan->nama_bahan }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($current_stok !== null)
                    <p class="text-sm mt-2">
                        Stok Saat Ini:
                        <b>{{ number_format($current_stok, 0) }} {{ $current_satuan }}</b>
                    </p>
                @endif
            </div>
        </div>

        <div class="col-span-6">
            <label class="form-label">Jumlah</label>
            <input type="number" wire:model="qty" step="1" inputmode="numeric" class="form-control"
                placeholder="Contoh: 50" required>



        </div>

        <div class="col-span-6">
            <label class="form-label">Keterangan</label>
            <input type="text" wire:model="keterangan" class="form-control" placeholder="contoh: Beli dari supplier">
        </div>

        <div class="col-span-12 mt-4">
            <button class="w-full btn btn-primary-600 text-center justify-center" type="submit">

                {{ $submit }} Stok
            </button>
        </div>


    </form>


</div>
