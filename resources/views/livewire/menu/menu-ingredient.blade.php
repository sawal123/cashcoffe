<div>

 

    <div class="mb-4" wire:ignore>
        <div x-data x-init="new TomSelect($refs.menuSelect, {
            allowEmptyOption: true,
            create: false,
            sortField: { field: 'text', direction: 'asc' }
        });">
            <label class="font-semibold mb-1 block">Pilih Menu</label>

            <select x-ref="menuSelect" wire:model.live="menu_id" class="">
                <option value="">Pilih Menu:</option>
                @foreach ($menus as $menu)
                    <option value="{{ $menu->id }}">{{ $menu->nama_menu }}</option>
                @endforeach
            </select>
        </div>

    </div>

    {{-- FORM TAMBAH BAHAN --}}
    {{-- @if ($menu_id) --}}
    {{-- <hr class="my-3"> --}}

    <div class="card p-5 rounded-xl shadow border border-neutral-200 dark:border-neutral-700">
        <h3 class="font-bold text-lg mb-4">Tambah Komposisi</h3>

        <div class="grid grid-cols-12 gap-4">

            {{-- Select Ingredient --}}
            <div class="col-span-12 md:col-span-6" wire:ignore>
                <label class="font-semibold block mb-1">Bahan</label>
                <select id="ingredientSelect" wire:model="ingredient_id" class="">
                    <option value="">Pilih Bahan:</option>
                    @foreach ($ingredients as $i)
                        <option value="{{ $i->id }}">
                            {{ $i->nama_bahan }} ({{ $i->satuan }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Qty --}}
            <div class="col-span-12 md:col-span-6">
                <label class="font-semibold block mb-1">Qty</label>
                <input type="number" wire:model="qty"
                    class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 
                       bg-white dark:bg-neutral-800 text-neutral-900 dark:text-neutral-200 
                       px-3 py-2 h-[42px]">
            </div>

            {{-- Button --}}
            <div class="col-span-12 md:col-span-2 flex md:items-end">
                <button wire:click="addIngredient"
                    class="w-full rounded-lg py-2 h-[42px] bg-blue-600 hover:bg-blue-700 
                       text-white font-semibold mt-2 md:mt-0">
                    Tambah
                </button>
            </div>

        </div>
    </div>




    {{-- LIST KOMPOSISI --}}
    <div class="card p-5 rounded-xl shadow border border-neutral-200 dark:border-neutral-700 mt-6">

        <h6 class="font-bold text-lg mb-4">Komposisi Saat Ini:</h6>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-neutral-100 dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-700">
                        <th class="text-left px-3 py-2 font-semibold text-sm">Bahan</th>
                        <th class="text-left px-3 py-2 font-semibold text-sm">Qty</th>
                        <th class="w-16"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($this->menuIngredients as $row)
                        <tr class="border-b border-neutral-200 dark:border-neutral-700">
                            <td class="px-3 py-2">
                                {{ $row->ingredient->nama_bahan }}
                            </td>
                            <td class="px-3 py-2">
                                {{ $row->qty }} {{ $row->ingredient->satuan }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                <button wire:click="removeIngredient({{ $row->id }})"
                                    class="px-3 py-1.5 rounded-lg text-white bg-red-500 hover:bg-red-600 
                                       text-sm font-semibold shadow-sm transition">
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>


    {{-- @endif --}}



</div>
