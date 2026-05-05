<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Cabang' }}</h6>
        <x-breadcrumb :title="$title ?? 'Cabang'" />
    </div>

    <x-toast />
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-neutral-800 dark:text-neutral-200">Daftar Cabang</h2>
        <x-ui.button wire:click="create" class="flex items-center gap-2">
            <iconify-icon icon="lucide:plus"></iconify-icon>
            Tambah Cabang
        </x-ui.button>
    </div>

    <!-- Table Branches -->
    <x-ui.table :headers="['Kode', 'Cabang', 'Tier Harga', 'Alamat', ['name' => 'Status', 'align' => 'center'], ['name' => 'Aksi', 'align' => 'center']]">
        @forelse($branches as $b)
            <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors">
                <td data-label="Kode" class="px-6 py-4 whitespace-nowrap font-semibold text-purple-600 dark:text-purple-400">
                    {{ $b->kode_cabang }}
                </td>
                <td data-label="Cabang" class="px-6 py-4 font-bold text-neutral-800 dark:text-neutral-200 whitespace-nowrap">
                    {{ $b->nama_cabang }}
                    <div class="text-xs font-normal text-neutral-500 mt-1">Telp: {{ $b->no_telp ?? '-' }}</div>
                </td>
                <td data-label="Tier Harga" class="px-6 py-4">
                    <span
                        class="px-3 py-1 bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg font-bold text-xs uppercase tracking-wider">
                        {{ $b->priceTier->nama_tier ?? 'Not Set' }}
                    </span>
                </td>
                <td data-label="Alamat" class="px-6 py-4 max-w-xs truncate">
                    {{ $b->alamat ?? '-' }}
                </td>
                <td data-label="Status" class="px-6 py-4 text-center">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" wire:click="toggleStatus({{ $b->id }})" {{ $b->is_active ? 'checked' : '' }}>
                        <div
                            class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                        </div>
                    </label>
                </td>
                <td data-label="Aksi" class="px-6 py-4 text-center">
                    <button wire:click="edit({{ $b->id }})"
                        class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl transition-colors">
                        <iconify-icon icon="lucide:edit" class="text-lg"></iconify-icon>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-neutral-500">Belum ada cabang terdaftar.
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <!-- Modal Create / Edit -->
    <x-mdal name="modal-branch">
        <div class="p-6">
            <h3 class="text-xl font-bold text-neutral-800 dark:text-neutral-200 mb-6">
                {{ $isEdit ? 'Edit Cabang' : 'Tambah Cabang Baru' }}
            </h3>

            <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-ui.input label="Kode Cabang" wire:model="kode_cabang" placeholder="Misal: JKT01" required />
                    <x-ui.select label="Tier Harga" wire:model="price_tier_id" required>
                        <option value="">-- Pilih Tier --</option>
                        @foreach ($tiers as $tier)
                            <option value="{{ $tier->id }}">{{ $tier->nama_tier }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <x-ui.input label="Nama Cabang" wire:model="nama_cabang" placeholder="Misal: Cabang Sudirman"
                    required />

                <div>
                    <label class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-2 block">Alamat
                        (Opsional)</label>
                    <textarea wire:model="alamat"
                        class="w-full bg-neutral-50 dark:bg-neutral-900 border-0 rounded-2xl px-4 py-3 placeholder:text-neutral-400 focus:ring-2 focus:ring-blue-500 text-neutral-800 dark:text-neutral-200"
                        rows="3" placeholder="Alamat lengkap cabang"></textarea>
                </div>

                <x-ui.input label="No Telepon (Opsional)" wire:model="no_telp" placeholder="0812xxxxxx" />

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" wire:click="closeModal"
                        class="px-6 py-3 font-semibold text-neutral-600 hover:bg-neutral-100 rounded-2xl transition-colors dark:text-neutral-400 dark:hover:bg-neutral-800">Batal</button>
                    <x-ui.button type="submit">
                        {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Cabang' }}
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-mdal>
</div>