<div>
    <x-toast />

    <div
        class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-3xl p-6 shadow-sm">
        <form wire:submit.prevent="{{ $stockId ? 'updateStok' : 'tambahStok' }}" class="space-y-6">

            {{-- Select Ingredient --}}
            <div wire:ignore>
                <div x-data x-init="const ts = new TomSelect($refs.menuSelect, {
                    allowEmptyOption: true,
                    create: false,
                    sortField: { field: 'text', direction: 'asc' }
                });
                
                $nextTick(() => {
                    @this.ingredient_id && ts.setValue(@this.ingredient_id);
                });
                
                ts.on('change', function(value) {
                    @this.set('ingredient_id', value);
                });">

                    <x-ui.select label="Pilih Bahan" x-ref="menuSelect">
                        <option value="">Pilih Bahan:</option>
                        @foreach ($ingredients as $bahan)
                            <option value="{{ $bahan->id }}">{{ $bahan->nama_bahan }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
            </div>

            {{-- Current Stock Info --}}
            @if ($current_stok !== null)
                <div
                    class="flex items-center gap-3 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-2xl border border-blue-100 dark:border-blue-800 transition-all">
                    <div
                        class="w-10 h-10 flex items-center justify-center bg-blue-600 text-white rounded-xl shadow-lg shadow-blue-500/30">
                        <iconify-icon icon="lucide:package-check" class="text-lg"></iconify-icon>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-blue-600/60 dark:text-blue-400/60 uppercase tracking-widest">
                            Stok Saat Ini</p>
                        <p class="text-lg font-black text-blue-900 dark:text-blue-100 italic">
                            {{ number_format($current_stok, 0, ',', '.') }} <span
                                class="text-sm font-bold opacity-60">{{ $current_satuan }}</span>
                        </p>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Quantity Input --}}
                <x-ui.input label="Jumlah Stok" type="number" wire:model="qty" step="1" inputmode="numeric"
                    placeholder="Contoh: 50" required prefix="QTY" />

                {{-- Description Input --}}
                <x-ui.input label="Keterangan" type="text" wire:model="keterangan"
                    placeholder="Contoh: Restock bulanan" prefix="MSG" />
            </div>

            {{-- Submit Button --}}
            <div class="pt-4">
                <x-ui.button type="submit" color="blue"
                    class="w-full font-bold tracking-widest  uppercase shadow-xl shadow-blue-500/20 active:scale-[0.98]">
                    <div class="flex items-center justify-center gap-3">
                        <iconify-icon icon="lucide:save"></iconify-icon>
                        <span>{{ $submit ?? 'Simpan' }} Stok</span>
                    </div>
                </x-ui.button>
            </div>
        </form>
    </div>
</div>
