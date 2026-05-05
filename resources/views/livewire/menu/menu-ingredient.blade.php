<div class="">

    {{-- SELECT MENU --}}
    <div class="mb-6" wire:ignore>
        <label
            class="block text-[11px] font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-2">Pilih
            Menu</label>
        <div x-data
            x-init="new TomSelect($refs.menuSelect, { allowEmptyOption: true, create: false, sortField: { field: 'text', direction: 'asc' } })">
            <select x-ref="menuSelect" wire:model.live="menu_id"
                class="w-full bg-white dark:bg-[#161b27] border border-slate-200 dark:border-[#2a3045] text-slate-800 dark:text-slate-200 text-sm px-4 py-3 rounded-xl outline-none focus:border-amber-500 transition appearance-none">
                <option value="">Pilih Menu:</option>
                @foreach ($menus as $menu)
                    <option value="{{ $menu->id }}">{{ $menu->nama_menu }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- TAMBAH KOMPOSISI --}}
    <div
        class="bg-white dark:bg-[#161b27] border border-slate-200 dark:border-[#1e2a3a] rounded-2xl p-6 mb-4 shadow-sm dark:shadow-none">
        <h3 class="text-[15px] font-semibold text-slate-800 dark:text-slate-100 mb-5 flex items-center gap-2">
            <span class="inline-block w-[3px] h-4 bg-amber-400 rounded-full"></span>
            Tambah Komposisi
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" wire:ignore>
            <div>
                <label
                    class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Bahan</label>
                <select id="ingredientSelect" wire:model="ingredient_id"
                    class="w-full bg-slate-50 dark:bg-[#0f1117] border border-slate-200 dark:border-[#1e2a3a] text-slate-800 dark:text-slate-200 text-sm px-4 py-2.5 rounded-xl outline-none focus:border-amber-500 transition appearance-none">
                    <option value="">Pilih Bahan:</option>
                    @foreach ($ingredients as $i)
                        <option value="{{ $i->id }}">{{ $i->nama_bahan }} ({{ $i->satuan->nama_satuan }})</option>
                    @endforeach
                </select>
            </div>
            <div class="">
                <label
                    class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">Qty</label>
                <input type="number" wire:model="qty" class="w-full rounded-xl border border-slate-200 dark:border-[#1e2a3a] 
                       bg-slate-50 dark:bg-[#0f1117] text-slate-800 dark:text-slate-200 
                       px-4 py-2.5 outline-none focus:border-amber-500 transition appearance-none">
            </div>
        </div>

        <button wire:click="addIngredient"
            class="mt-4 w-full justify-center bg-gradient-to-r from-amber-400 to-amber-500 dark:from-amber-500 dark:to-amber-600 text-slate-900 dark:text-[#0f1117] font-bold text-sm py-2.5 rounded-xl hover:opacity-90 transition flex items-center gap-2">
            + Tambah
        </button>
    </div>

    {{-- LIST KOMPOSISI --}}
    <div
        class="bg-white dark:bg-[#161b27] border border-slate-200 dark:border-[#1e2a3a] rounded-2xl p-6 shadow-sm dark:shadow-none">
        <h3 class="text-[15px] font-semibold text-slate-800 dark:text-slate-100 mb-5 flex items-center gap-2">
            <span class="inline-block w-[3px] h-4 bg-amber-400 rounded-full"></span>
            Komposisi Saat Ini
        </h3>

        {{-- FLASH MESSAGE --}}
        @if (session()->has('success'))
            <div
                class="flex items-center gap-2 bg-emerald-50 dark:bg-[#0d2b1e] border border-emerald-200 dark:border-[#134d33] text-emerald-600 dark:text-emerald-400 text-sm rounded-xl px-4 py-2.5 mb-4">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                {{ session('success') }}
            </div>
        @endif

        <x-ui.table :headers="['Bahan', 'Qty', 'HPP', ['name' => 'Aksi', 'align' => 'right']]">
            @foreach ($this->menuIngredients as $row)
                <tr class="hover:bg-slate-50 dark:hover:bg-[#1a2133] transition">
                    <td data-label="Bahan" class="px-6 py-4 text-sm font-medium text-slate-700 dark:text-slate-100">
                        {{ $row->ingredient->nama_bahan }}
                    </td>
                    <td data-label="Qty" class="px-6 py-4">
                        <span
                            class="bg-slate-100 dark:bg-[#1e2a3a] text-slate-600 dark:text-slate-400 text-xs font-mono px-2.5 py-1 rounded-full">
                            {{ number_format($row->qty, 0) }} {{ $row->ingredient->satuan->nama_satuan }}
                        </span>
                    </td>
                    <td data-label="HPP"
                        class="px-6 py-4 text-sm font-mono font-medium text-emerald-600 dark:text-emerald-400">
                        Rp {{ number_format($row->qty * $row->ingredient->hpp, 0, ',', '.') }}
                    </td>
                    <td data-label="Aksi" class="px-6 py-4 text-right">
                        <button wire:click="removeIngredient({{ $row->id }})"
                            class="text-red-500 hover:text-red-700 font-bold text-xs uppercase tracking-wider transition">
                            Hapus
                        </button>
                    </td>
                </tr>
            @endforeach

            @if($menu_id && $this->menuIngredients->isNotEmpty())
                <tr
                    class="bg-amber-50/50 dark:bg-amber-900/10 border-t-2 border-amber-200 dark:border-amber-900/50 font-bold">
                    <td data-label="Ringkasan" colspan="2"
                        class="px-6 py-5 text-right text-sm text-amber-800 dark:text-amber-400">
                        TOTAL HPP MENU
                    </td>
                    <td data-label="Total HPP"
                        class="px-6 py-5 text-amber-600 dark:text-amber-500 text-lg font-black font-mono">
                        Rp {{ number_format($this->total_hpp, 0, ',', '.') }}
                    </td>
                    <td data-label="Aksi" class="px-6 py-5 text-right">
                        <button wire:click="saveHpp"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-lg shadow-emerald-600/20 transition-all active:scale-95 whitespace-nowrap">
                            <iconify-icon icon="lucide:save" class="text-sm"></iconify-icon>
                            Simpan HPP
                        </button>
                    </td>
                </tr>
            @endif
        </x-ui.table>
    </div>

</div>