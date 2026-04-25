<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Tier Harga' }}</h6>
        <x-breadcrumb :title="$title ?? 'Tier Harga'" />
    </div>

    <x-toast />
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-neutral-800 dark:text-neutral-200">Daftar Tier Harga</h2>
        <x-ui.button wire:click="create" class="flex items-center gap-2">
            <iconify-icon icon="lucide:plus"></iconify-icon>
            Tambah Tier
        </x-ui.button>
    </div>

    <!-- Table Tiers -->
    <div class="overflow-x-auto border border-neutral-200 dark:border-neutral-700 rounded-2xl">
        <table class="w-full text-left text-sm text-neutral-600 dark:text-neutral-400">
            <thead class="bg-neutral-50 dark:bg-neutral-800/50 text-neutral-800 dark:text-neutral-200 uppercase text-xs font-bold border-b border-neutral-200 dark:border-neutral-700">
                <tr>
                    <th class="px-6 py-4">Nama Tier</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                @forelse($tiers as $t)
                <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors">
                    <td class="px-6 py-4 font-bold text-neutral-800 dark:text-neutral-200 whitespace-nowrap">
                        {{ $t->nama_tier }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" wire:click="toggleStatus({{ $t->id }})" {{ $t->is_active ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-400 peer-focus:outline-none rounded-full peer dark:bg-gray-500 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button wire:click="edit({{ $t->id }})" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl transition-colors">
                            <iconify-icon icon="lucide:edit" class="text-lg"></iconify-icon>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-6 py-8 text-center text-neutral-500">Belum ada tier harga terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Create / Edit -->
    <x-mdal name="modal-tier">
        <div class="p-6">
            <h3 class="text-xl font-bold text-neutral-800 dark:text-neutral-200 mb-6">
                {{ $isEdit ? 'Edit Tier Harga' : 'Tambah Tier Baru' }}
            </h3>
            
            <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}" class="space-y-4">
                <x-ui.input label="Nama Tier" wire:model="nama_tier" placeholder="Misal: Tier Mall" required />
                
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" wire:click="closeModal" class="px-6 py-3 font-semibold text-neutral-600 hover:bg-neutral-100 rounded-2xl transition-colors dark:text-neutral-400 dark:hover:bg-neutral-800">Batal</button>
                    <x-ui.button type="submit">
                        {{ $isEdit ? 'Simpan' : 'Tambah Tier' }}
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-mdal>
</div>
