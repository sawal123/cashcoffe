<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Grup Varian' }}</h6>
        <x-breadcrumb :title="$title ?? 'Grup Varian'" />
    </div>

    <div
        class="p-6 bg-white dark:bg-neutral-800 rounded-3xl shadow-sm border border-neutral-100 dark:border-neutral-700">
        <x-toast />
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Manajemen Grup Varian</h1>
                <p class="text-slate-500">Kelola pilihan tambahan untuk menu Anda</p>
            </div>
            <x-ui.button wire:click="openCreateModal">
                + Grup Baru
            </x-ui.button>
        </div>

        <div
            class="bg-white dark:bg-neutral-800 rounded-3xl shadow-sm border border-neutral-100 dark:border-neutral-700 overflow-hidden">
            <div
                class="p-6 border-b border-neutral-100 dark:border-neutral-700 bg-neutral-50/50 dark:bg-neutral-800/50">
                <x-ui.input wire:model.live="search" placeholder="Cari grup..." class="max-w-xs" />
            </div>

            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-600 text-sm uppercase">
                    <tr>
                        <th class="px-6 py-4 font-bold">Nama Grup</th>
                        <th class="px-6 py-4 font-bold">Tipe Seleksi</th>
                        <th class="px-6 py-4 font-bold">Wajib?</th>
                        <th class="px-6 py-4 font-bold">Jumlah Opsi</th>
                        <th class="px-6 py-4 font-bold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($groups as $group)
                        {{-- Row Grup Utama --}}
                        <tr class="hover:bg-slate-50 dark:hover:bg-neutral-900/30 transition">
                            <td class="px-6 py-4 text-slate-800 dark:text-neutral-200 font-bold">{{ $group->nama_group }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-bold {{ $group->selection_type === 'single' ? 'bg-blue-50 text-blue-600' : 'bg-purple-50 text-purple-600' }}">
                                    {{ ucfirst($group->selection_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($group->is_required)
                                    <span class="text-emerald-600 flex items-center gap-1 text-sm">
                                        <iconify-icon icon="lucide:check-circle"></iconify-icon> Ya
                                    </span>
                                @else
                                    <span class="text-slate-400 text-sm">Tidak</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600 dark:text-neutral-400">{{ $group->options_count }} Opsi</td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button wire:click="edit({{ $group->id }})"
                                    class="text-blue-600 hover:text-blue-800 font-bold text-sm">Edit Grup</button>
                                <button wire:click="delete({{ $group->id }})" wire:confirm="Yakin ingin menghapus grup ini?"
                                    class="text-red-500 hover:text-red-700 font-bold text-sm">Hapus</button>
                            </td>
                        </tr>
                        {{-- Sub-rows: Opsi Varian dengan tombol Resep Bahan --}}
                        @foreach ($group->options as $option)
                            <tr class="bg-neutral-50/70 dark:bg-neutral-900/20 border-l-4 border-l-amber-300">
                                <td class="px-6 py-3 pl-10" colspan="1">
                                    <div class="flex items-center gap-2">
                                        <iconify-icon icon="lucide:corner-down-right"
                                            class="text-neutral-400 text-sm"></iconify-icon>
                                        <span
                                            class="text-sm font-semibold text-neutral-700 dark:text-neutral-300">{{ $option->nama_opsi }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-3" colspan="2">
                                    <span class="text-xs text-neutral-500">
                                        + Rp {{ number_format($option->extra_price, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-xs text-neutral-400">
                                    {{ $option->ingredients_count ?? 0 }} bahan
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('variant-option.ingredients', base64_encode($option->id)) }}"
                                        wire:navigate
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-bold transition">
                                        <iconify-icon icon="lucide:flask-conical" class="text-sm"></iconify-icon>
                                        Resep Bahan
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic">Belum ada grup varian.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="p-4 border-t border-slate-200">
                {{ $groups->links() }}
            </div>
        </div>

        {{-- MODAL CRUD --}}
        @if($showModal)
            <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm p-4">
                <div
                    class="bg-white dark:bg-neutral-800 rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

                    {{-- Modal Header --}}
                    <div
                        class="px-8 py-6 border-b border-neutral-100 dark:border-neutral-700 flex justify-between items-center bg-white dark:bg-neutral-800">
                        <div>
                            <h3 class="text-2xl font-bold text-neutral-800 dark:text-neutral-100">
                                {{ $isEdit ? 'Edit' : 'Tambah' }} Grup Varian
                            </h3>
                            <p class="text-neutral-500 dark:text-neutral-400 mt-1">
                                Configure options and pricing for product variations.
                            </p>
                        </div>
                        <button wire:click="$set('showModal', false)"
                            class="text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200 transition-colors">
                            <iconify-icon icon="lucide:x" class="text-2xl"></iconify-icon>
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-8 overflow-y-auto space-y-8 flex-1 bg-white dark:bg-neutral-800">
                        {{-- Form Fields --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-ui.input label="Nama Grup" wire:model="nama_group" placeholder="Contoh: Ukuran, Warna" />

                            <x-ui.select label="Tipe Seleksi" wire:model="selection_type">
                                <option value="single">Pilih satu (Single)</option>
                                <option value="multiple">Pilih banyak (Multiple)</option>
                            </x-ui.select>
                        </div>

                        {{-- Required Checkbox --}}
                        <div class="flex items-center gap-3">
                            <input type="checkbox" wire:model="is_required" id="is_req"
                                class="w-6 h-6 rounded-lg border-neutral-300 dark:border-neutral-600 text-blue-600 focus:ring-blue-500 transition-all cursor-pointer">
                            <label for="is_req"
                                class="text-sm font-semibold text-neutral-700 dark:text-neutral-300 cursor-pointer">
                                Wajib dipilih oleh pelanggan?
                            </label>
                        </div>

                        {{-- Options Section --}}
                        <div class="space-y-6">
                            <div class="flex justify-between items-center">
                                <h4 class="text-xs font-bold text-neutral-400 uppercase tracking-widest">OPSI PILIHAN</h4>
                                <button wire:click="addOption"
                                    class="flex items-center gap-2 text-sm text-blue-600 font-bold hover:text-blue-700 transition-all">
                                    <iconify-icon icon="lucide:plus-circle" class="text-lg"></iconify-icon>
                                    Tambah Opsi
                                </button>
                            </div>

                            <div class="space-y-4">
                                @foreach($options as $index => $option)
                                    <div class="flex gap-4 items-center animate-in fade-in slide-in-from-top-2 duration-300">
                                        <div class="flex-1">
                                            <x-ui.input wire:model="options.{{ $index }}.nama_opsi" placeholder="Nama Opsi" />
                                        </div>
                                        <div class="w-48">
                                            <x-ui.input type="number" wire:model="options.{{ $index }}.extra_price" prefix="Rp"
                                                placeholder="0" />
                                        </div>
                                        <button wire:click="removeOption({{ $index }})"
                                            class="p-2 text-neutral-400 hover:text-red-500 transition-colors rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20">
                                            <iconify-icon icon="lucide:trash-2" class="text-xl"></iconify-icon>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div
                        class="px-8 py-6 border-t border-neutral-100 dark:border-neutral-700 bg-neutral-50/30 dark:bg-neutral-800 flex justify-end items-center gap-8">
                        <button wire:click="$set('showModal', false)"
                            class="text-sm font-bold text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200 transition-colors">
                            Batal
                        </button>
                        <x-ui.button wire:click="save">
                            Simpan Grup
                        </x-ui.button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>