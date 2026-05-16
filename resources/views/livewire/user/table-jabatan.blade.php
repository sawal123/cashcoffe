<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="/user" wire:navigate class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-400 hover:text-primary-600 transition-all shadow-sm">
                <iconify-icon icon="lucide:arrow-left" class="text-xl"></iconify-icon>
            </a>
            <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">Kelola Jabatan Karyawan</h6>
        </div>
        <x-breadcrumb title="Kelola Jabatan" />
    </div>

    <x-toast />

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div class="flex flex-wrap gap-2">
            <div class="sm:w-[300px]">
                <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Cari nama jabatan..."
                    class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
            </div>
        </div>
        <x-ui.button @click="$dispatch('open-modal', { name: 'modal-jabatan' }); $wire.resetFields()" color="blue">
            <iconify-icon icon="mingcute:add-circle-line" class="mr-2 text-lg align-middle"></iconify-icon>
            Tambah Jabatan
        </x-ui.button>
    </div>

    <x-ui.table :headers="[
        ['name' => '#', 'align' => 'center'],
        'Nama Jabatan',
        ['name' => 'Jumlah Karyawan', 'align' => 'center'],
        ['name' => 'Aksi', 'align' => 'center'],
    ]">
        @forelse ($jabatans as $jab)
            <tr wire:key="{{ $jab->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition">
                <td class="px-6 py-4 text-center text-sm text-neutral-500">
                    {{ ($jabatans->currentPage() - 1) * $jabatans->perPage() + $loop->iteration }}
                </td>
                <td class="px-6 py-4">
                    <span class="font-semibold text-neutral-800 dark:text-neutral-200">
                        {{ $jab->nama_jabatan }}
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                        {{ $jab->users_count }} Orang
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex justify-center gap-2">
                        <button type="button" wire:click="edit({{ $jab->id }})"
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-700 transition-colors shadow-sm border border-blue-100" title="Edit">
                            <iconify-icon icon="lucide:edit" class="text-lg"></iconify-icon>
                        </button>
                        <button type="button" 
                            @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ $jab->id }} })"
                            class="flex h-8 w-8 items-center justify-center rounded-xl bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 transition-colors shadow-sm border border-red-100" title="Hapus">
                            <iconify-icon icon="lucide:trash-2" class="text-lg"></iconify-icon>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center py-12 text-neutral-500">
                    Tidak ada data jabatan ditemukan.
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-4">
        {{ $jabatans->links() }}
    </div>

    {{-- Modal Add/Edit --}}
    <x-mdal name="modal-jabatan">
        <div class="px-6 py-4">
            <h3 class="font-bold text-lg text-center mb-1 text-neutral-900 dark:text-neutral-100">
                {{ $isEdit ? 'Edit Jabatan' : 'Tambah Jabatan Baru' }}
            </h3>
            <p class="text-neutral-500 text-sm text-center mb-6">
                Masukkan nama jabatan yang akan digunakan pada profil karyawan.
            </p>

            <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}">
                <div class="space-y-4">
                    <x-ui.input wire:model="nama_jabatan" label="Nama Jabatan *"
                        placeholder="Contoh: Barista, Kasir, Waiter" class="!bg-white border border-neutral-200" />
                </div>

                <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-neutral-100">
                    <button type="button" x-on:click="$dispatch('close-modal', { name: 'modal-jabatan' })"
                        class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                        Batal
                    </button>

                    <x-ui.button type="submit" color="blue" class="!px-5 !py-2.5">
                        <iconify-icon icon="mingcute:check-line" class="mr-1"></iconify-icon> Simpan
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-mdal>

    {{-- Confirm Delete Modal --}}
    <x-mdal name="confirm-delete">
        <div class="px-6 py-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-rose-100 text-rose-600 shadow-sm border border-rose-200">
                <iconify-icon icon="lucide:alert-triangle" class="text-2xl"></iconify-icon>
            </div>
            <h3 class="mb-1 text-lg font-bold text-neutral-900 dark:text-neutral-100">Hapus Jabatan?</h3>
            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Tindakan ini tidak dapat dibatalkan. Jabatan akan dihapus permanen.
            </p>
            <div class="flex justify-center gap-3 border-t pt-6 border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'confirm-delete' })"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                    Batal
                </button>
                <x-ui.button type="button" color="danger"
                    @click="$wire.delete(selectedId); $dispatch('close-modal', { name: 'confirm-delete' })"
                    class="!px-5 !py-2.5">
                    Ya, Hapus
                </x-ui.button>
            </div>
        </div>
    </x-mdal>
</div>
