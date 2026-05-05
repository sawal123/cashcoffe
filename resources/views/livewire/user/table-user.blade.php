<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'User' }}</h6>
        <x-breadcrumb :title="$title ?? 'User'" />
    </div>
    <x-toast />

    {{-- Header Controls (Pagination & Search) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">

        {{-- Bagian Kiri: Droppage & Search --}}
        <div class="flex gap-2">
            <x-droppage perPage="{{ $perPage }}" />
            <div class="sm:w-[300px]">
                <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Cari user..."
                    class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
            </div>
        </div>

        {{-- Bagian Kanan: Button Add Role & Add User --}}
        <div class="flex gap-2">
            <x-ui.button @click="$dispatch('open-modal', { name: 'add-role' })" color="blue" class="!px-5 !py-2.5">
                <iconify-icon icon="mingcute:add-circle-line" class="mr-2 text-lg align-middle"></iconify-icon>
                Tambah Role
            </x-ui.button>
            <x-ui.button-link href="/user/create" icon="mingcute:add-circle-line">
                Tambah User
            </x-ui.button-link>
        </div>
    </div>

    <div class="mb-4">
        <h4 class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-2">Daftar Role Tersedia:</h4>

        <div class="flex flex-wrap items-center gap-2 pb-2">

            @forelse ($all_roles as $role)
                <div
                    class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg shadow-sm whitespace-nowrap group hover:border-blue-500 transition-colors">
                    <span class="text-sm font-medium text-neutral-700 dark:text-neutral-200">
                        {{ ucfirst($role->name) }}
                    </span>

                    @if ($role->name !== 'admin' && $role->name !== 'superadmin')
                        @if ($role->users_count > 0)
                            <span class="text-[10px] bg-neutral-100 text-neutral-500 px-1.5 py-0.5 rounded-md font-bold" title="Role sedang digunakan oleh {{ $role->users_count }} user">
                                {{ $role->users_count }} user
                            </span>
                        @else
                            <button type="button"
                                @click="$dispatch('open-modal', { name: 'delete-role', id: {{ json_encode(base64_encode($role->id)) }} })"
                                class="ml-1 text-neutral-400 hover:text-red-500 hover:bg-red-50 rounded-full p-0.5 transition-colors focus:outline-none"
                                title="Hapus Role">
                                <iconify-icon icon="mingcute:close-line" class="text-base block"></iconify-icon>
                            </button>
                        @endif
                    @endif
                </div>
            @empty
                <div class="text-sm text-neutral-400 italic">Belum ada role dibuat.</div>
            @endforelse

        </div>
    </div>

    <x-ui.table :headers="[
        ['name' => '#', 'align' => 'center'],
        ['name' => 'Avatar', 'align' => 'center'],
        'Nama',
        'Email',
        ['name' => 'Cabang', 'align' => 'center'],
        ['name' => 'Role', 'align' => 'center'],
        ['name' => 'Action', 'align' => 'center'],
    ]">
        @forelse ($users as $user)
            <tr wire:key="{{ $user->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition">
                <td data-label="#" class="px-6 py-4 text-center text-sm text-neutral-500">
                    {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                </td>

                <td data-label="Avatar" class="px-6 py-4 text-center">
                    @if ($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="Avatar"
                            class="w-10 h-10 rounded-full object-cover mx-auto border border-neutral-300">
                    @else
                        <div
                            class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mx-auto font-bold border border-blue-200">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                    @endif
                </td>

                <td data-label="Nama" class="px-6 py-4 break-words">
                    <span class="font-semibold text-neutral-800 dark:text-neutral-200">
                        {{ $user->name }}
                    </span>
                </td>

                <td data-label="Email" class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400 break-all">
                    {{ $user->email }}
                </td>

                <td data-label="Cabang" class="px-6 py-4 text-center">
                    @if ($user->branch)
                        <span
                            class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-blue-50 text-blue-600 border border-blue-100 uppercase">
                            {{ $user->branch->nama_cabang }}
                        </span>
                    @else
                        <span
                            class="px-2.5 py-1 text-[10px] font-bold rounded-lg bg-neutral-100 text-neutral-500 border border-neutral-200 uppercase">
                            HQ / PUSAT
                        </span>
                    @endif
                </td>

                <td data-label="Role" class="px-6 py-4 text-center">
                    <div class="flex flex-wrap justify-center gap-1">
                        @forelse ($user->roles as $role)
                            <span
                                class="px-2 py-1 text-[10px] font-bold rounded-full bg-neutral-100 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-300 uppercase">
                                {{ ucfirst($role->name) }}
                            </span>
                        @empty
                            <span class="text-[10px] uppercase text-neutral-400 italic">No Role</span>
                        @endforelse
                    </div>
                </td>

                <td data-label="Aksi" class="px-6 py-4 text-center">
                    <div class="flex justify-center gap-2">
                        <x-ui.action-edit href="/user/{{ base64_encode($user->id) }}/edit" wire:navigate />
                        <x-ui.action-delete
                            @click="$dispatch('open-modal', { name: 'delete-user', id: {{ json_encode(base64_encode($user->id)) }} })" />
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-12 text-neutral-500">
                    <div class="flex flex-col items-center justify-center gap-3">
                        <iconify-icon icon="mingcute:ghost-line" class="text-4xl"></iconify-icon>
                        <span class="text-sm">Tidak ada data user ditemukan.</span>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $users->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    <x-mdal name="add-role">
        <div class="px-6 py-4">
            <h3 class="font-bold text-lg text-center mb-1 text-neutral-900">Tambah Role Baru</h3>
            <p class="text-neutral-500 text-sm text-center mb-6">Buat hak akses baru untuk sistem.</p>

            <form wire:submit.prevent="createRole">
                <div class="space-y-4">
                    <x-ui.input wire:model="newRoleName" label="Nama Role *"
                        placeholder="Contoh: supervisor, admin-gudang" class="!bg-white border border-neutral-200" />
                </div>

                <div class="mt-8 flex justify-end gap-3 pt-4 border-t border-neutral-100">
                    <button type="button" x-on:click="$dispatch('close-modal', { name: 'add-role' })"
                        class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                        Batal
                    </button>

                    <x-ui.button type="submit" color="blue" class="!px-5 !py-2.5">
                        <iconify-icon icon="mingcute:check-line" class="mr-1"></iconify-icon> Simpan Role
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-mdal>

    <x-mdal name="delete-role">
        <div class="px-6 py-6 text-center">
            <div
                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-rose-100 text-rose-600 shadow-sm border border-rose-200">
                <iconify-icon icon="lucide:alert-triangle" class="text-2xl"></iconify-icon>
            </div>

            <h3 class="mb-1 text-lg font-bold text-neutral-900 dark:text-neutral-100">Hapus Role Ini?</h3>
            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Tindakan ini tidak dapat dibatalkan. Role akan hilang dari sistem secara permanen.
            </p>

            <div class="flex justify-center gap-3 border-t pt-6 border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'delete-role' })"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                    Batal
                </button>

                <x-ui.button type="button" color="danger"
                    @click="$wire.deleteRole(selectedId); $dispatch('close-modal', { name: 'delete-role' })"
                    class="!px-5 !py-2.5">
                    Ya, Hapus Role
                </x-ui.button>
            </div>
        </div>
    </x-mdal>

    <x-mdal name="delete-user">
        <div class="px-6 py-6 text-center">
            <div
                class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-rose-100 text-rose-600 shadow-sm border border-rose-200">
                <iconify-icon icon="lucide:alert-triangle" class="text-2xl"></iconify-icon>
            </div>

            <h3 class="mb-1 text-lg font-bold text-neutral-900 dark:text-neutral-100">Hapus User Ini?</h3>
            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Tindakan ini tidak dapat dibatalkan. User akan dihapus dari sistem beserta akses loginnya.
            </p>

            <div class="flex justify-center gap-3 border-t pt-6 border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'delete-user' })"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                    Batal
                </button>

                <x-ui.button type="button" color="danger"
                    @click="$wire.deleteUser(selectedId); $dispatch('close-modal', { name: 'delete-user' })"
                    class="!px-5 !py-2.5">
                    Ya, Hapus User
                </x-ui.button>
            </div>
        </div>
    </x-mdal>
</div>
