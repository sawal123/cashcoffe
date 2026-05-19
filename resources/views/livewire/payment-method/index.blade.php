<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Metode Pembayaran' }}</h6>
        <x-breadcrumb :title="$title ?? 'Metode Pembayaran'" />
    </div>

    <x-toast />
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-neutral-800 dark:text-neutral-200">Daftar Metode Pembayaran</h2>
        <x-ui.button wire:click="create" class="flex items-center gap-2">
            <iconify-icon icon="lucide:plus"></iconify-icon>
            Tambah Metode
        </x-ui.button>
    </div>

    <!-- Table Methods -->
    <x-ui.table :headers="['Nama Metode', 'Kode Metode', ['name' => 'Status', 'align' => 'center'], ['name' => 'Aksi', 'align' => 'center']]">
        @forelse($methods as $m)
        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors">
            <td data-label="Nama Metode" class="px-6 py-4 font-bold text-neutral-800 dark:text-neutral-200 whitespace-nowrap">
                {{ $m->nama_metode }}
            </td>
            <td data-label="Kode Metode" class="px-6 py-4 text-neutral-600 dark:text-neutral-400 whitespace-nowrap">
                <code>{{ $m->kode_metode }}</code>
            </td>
            <td data-label="Status" class="px-6 py-4 text-center">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer" wire:click="toggleStatus({{ $m->id }})" {{ $m->is_active ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-400 peer-focus:outline-none rounded-full peer dark:bg-gray-500 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                </label>
            </td>
            <td data-label="Aksi" class="px-6 py-4 text-center">
                <button wire:click="edit({{ $m->id }})" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl transition-colors">
                    <iconify-icon icon="lucide:edit" class="text-lg"></iconify-icon>
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="4" class="px-6 py-8 text-center text-neutral-500">Belum ada metode pembayaran terdaftar.</td>
        </tr>
        @endforelse
    </x-ui.table>

    <!-- Modal Create / Edit -->
    <x-mdal name="modal-payment-method">
        <div class="p-6">
            <h3 class="text-xl font-bold text-neutral-800 dark:text-neutral-200 mb-6">
                {{ $isEdit ? 'Edit Metode Pembayaran' : 'Tambah Metode Pembayaran' }}
            </h3>
            
            <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}" class="space-y-4">
                <x-ui.input label="Nama Metode" wire:model="nama_metode" placeholder="Misal: Gopay" required />
                
                <x-ui.input label="Kode Metode (Opsional)" wire:model="kode_metode" placeholder="Misal: gopay (kosongkan untuk auto-generate)" />
                @error('kode_metode')
                    <span class="text-xs text-red-500 font-semibold block mt-1">{{ $message }}</span>
                @enderror
                
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" wire:click="closeModal" class="px-6 py-3 font-semibold text-neutral-600 hover:bg-neutral-100 rounded-2xl transition-colors dark:text-neutral-400 dark:hover:bg-neutral-800">Batal</button>
                    <x-ui.button type="submit">
                        {{ $isEdit ? 'Simpan' : 'Tambah Metode' }}
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-mdal>
</div>
