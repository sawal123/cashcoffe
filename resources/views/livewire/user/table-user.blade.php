<div>
    <x-toast />

    {{-- Header Controls (Pagination & Search) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">

        {{-- Bagian Kiri: Droppage & Search --}}
        <div class="flex gap-2">
            <x-droppage perPage="{{ $perPage }}" />
            <div class="sm:w-[300px]">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Cari user..." />
            </div>
        </div>

        {{-- Bagian Kanan: Button Add Role --}}
        <div class="flex gap-2">
            <div>
                <button @click="$dispatch('open-modal', { name: 'add-role' })"
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition">
                    <iconify-icon icon="mingcute:add-circle-line" class="mr-2 text-lg"></iconify-icon>
                    Tambah Role
                </button>
            </div>
            <div>
                <a href="/user/create" wire:navigate
                    class="inline-flex items-center justify-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition">
                    <iconify-icon icon="mingcute:add-circle-line" class="mr-2 text-lg"></iconify-icon>
                    Tambah User
                </a>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <h4 class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-2">Daftar Role Tersedia:</h4>

        <div class="flex flex-nowrap items-center gap-3 overflow-x-auto pb-2" style="scrollbar-width: thin;">

            @forelse ($all_roles as $role)
                <div
                    class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg shadow-sm whitespace-nowrap group hover:border-primary-500 transition-colors">

                    {{-- Nama Role --}}
                    <span class="text-sm font-medium text-neutral-700 dark:text-neutral-200">
                        {{ ucfirst($role->name) }}
                    </span>

                    {{-- Tombol Hapus (X) --}}
                    {{-- Kita sembunyikan tombol X jika itu 'super-admin' (opsional, demi keamanan) --}}
                    @if ($role->name !== 'admin')
                        <button type="button" {{-- Trigger AlpineJS untuk membuka modal & set ID --}}
                            @click="$dispatch('open-modal', { name: 'delete-role', id: {{ json_encode(base64_encode($role->id)) }} })"
                            class="ml-1 text-neutral-400 hover:text-red-500 hover:bg-red-50 rounded-full p-0.5 transition-colors focus:outline-none"
                            title="Hapus Role">
                            <iconify-icon icon="mingcute:close-line" class="text-base block"></iconify-icon>
                        </button>
                    @endif
                </div>
            @empty
                <div class="text-sm text-neutral-400 italic">Belum ada role dibuat.</div>
            @endforelse

        </div>
    </div>

    <div class="table-responsive">
        <table class="table basic-border-table mb-2">
            <thead>
                <tr>
                    {{-- # --}}
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 w-10 text-center">
                        #
                    </th>

                    {{-- Avatar --}}
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 w-16 text-center">
                        Avatar
                    </th>

                    {{-- Nama --}}
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-left">
                        Nama
                    </th>

                    {{-- Email --}}
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-left">
                        Email
                    </th>

                    {{-- Role --}}
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-center">
                        Role
                    </th>

                    {{-- Action --}}
                    <th class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-center w-32">
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr wire:key="{{ $user->id }}">
                        {{-- # --}}
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-center">
                            {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                        </td>

                        {{-- Avatar --}}
                        <td
                            class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-center py-2">
                            @if ($user->avatar)
                                <img src="{{ asset('storage/' . $user->avatar) }}" alt="Avatar"
                                    class="w-10 h-10 rounded-full object-cover mx-auto border border-neutral-300">
                            @else
                                {{-- Fallback jika tidak ada avatar (Inisial) --}}
                                <div
                                    class="w-10 h-10 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center mx-auto font-bold border border-primary-200">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                            @endif
                        </td>

                        {{-- Nama --}}
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0">
                            <span class="font-semibold text-neutral-800 dark:text-neutral-200">
                                {{ $user->name }}
                            </span>
                        </td>

                        {{-- Email --}}
                        <td
                            class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-neutral-600 dark:text-neutral-400">
                            {{ $user->email }}
                        </td>

                        {{-- Role --}}
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-center">
                            <div class="flex flex-wrap justify-center gap-1">
                                @forelse ($user->roles as $role)
                                    <span
                                        class="px-2 py-1 text-xs font-medium rounded-full bg-neutral-100 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-300">
                                        {{ ucfirst($role->name) }}
                                    </span>
                                @empty
                                    <span class="text-xs text-neutral-400 italic">No Role</span>
                                @endforelse
                            </div>
                        </td>

                        {{-- Action --}}
                        <td class="border-r border-neutral-200 dark:border-neutral-600 last:border-r-0 text-center">
                            <div class="flex justify-center gap-2">
                                {{-- Tombol Edit --}}
                                <a href="/user/{{ base64_encode($user->id) }}/edit" wire:navigate
                                    class="w-8 h-8 bg-warning-100 dark:bg-warning-600/25 text-warning-600 dark:text-warning-400 rounded-full inline-flex items-center justify-center transition hover:scale-110"
                                    title="Edit User">
                                    <iconify-icon icon="mingcute:edit-line"></iconify-icon>
                                </a>

                                {{-- Tombol Delete --}}
                                <button
                                    @click="$dispatch('open-modal', { name: 'delete-user', id: {{ json_encode(base64_encode($user->id)) }} })"
                                    class="w-8 h-8 bg-danger-100 dark:bg-danger-600/25 text-danger-600 dark:text-danger-400 rounded-full inline-flex items-center justify-center transition hover:scale-110"
                                    title="Hapus User">
                                    <iconify-icon icon="mingcute:delete-2-line"></iconify-icon>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-neutral-500">
                            <div class="flex flex-col items-center justify-center">
                                <iconify-icon icon="mingcute:ghost-line" class="text-4xl mb-2"></iconify-icon>
                                <span>Tidak ada data user ditemukan.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        {{ $users->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    <x-mdal name="add-role">
        <div class="px-6 py-4">
            <h3 class="font-semibold text-lg text-center mb-1">Tambah Role Baru</h3>
            <p class="text-neutral-500 text-sm text-center mb-6">Buat hak akses baru untuk sistem.</p>

            <form wire:submit.prevent="createRole">
                <div class="space-y-4">
                    {{-- Input Nama Role --}}
                    <div>
                        <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                            Nama Role <span class="text-red-500">*</span>
                        </label>
                        <x-input wire:model="newRoleName" placeholder="Contoh: supervisor, admin-gudang" />
                        @error('newRoleName')
                            <span class="text-xs text-danger-600 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="mt-8 flex justify-end gap-3 pt-4">
                    {{-- Tombol Batal (Menutup Modal) --}}
                    <button type="button" x-on:click="modalIsOpen = false"
                        class="px-4 py-2 rounded-lg border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-medium transition">
                        Batal
                    </button>

                    {{-- Tombol Simpan --}}
                    <button type="submit"
                        class="px-4 py-2 rounded-lg bg-primary-600 text-white hover:bg-primary-700 text-sm font-medium transition inline-flex items-center">
                        <iconify-icon icon="mingcute:check-line" class="mr-1"></iconify-icon>
                        Simpan Role
                    </button>
                </div>
            </form>
        </div>
    </x-mdal>

    <x-mdal name="delete-role">
        <div class="px-6 py-6 text-center">

            {{-- Icon Peringatan --}}
            <div
                class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-500">
                <iconify-icon icon="mingcute:warning-line" class="text-2xl"></iconify-icon>
            </div>

            <h3 class="mb-1 text-lg font-semibold text-neutral-800 dark:text-neutral-100">
                Hapus Role Ini?
            </h3>

            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Tindakan ini tidak dapat dibatalkan. Role akan hilang dari sistem.
            </p>

            {{-- Action Buttons --}}
            <div class="flex justify-center gap-3 border-t pt-4 border-neutral-100 dark:border-neutral-700">

                {{-- Tombol Cancel --}}
                <button type="button" x-on:click="modalIsOpen = false"
                    class="px-4 py-2 rounded-lg border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-medium transition">
                    Batal
                </button>

                {{-- Tombol Delete --}}
                {{-- Mengirim selectedId (dari Alpine) ke fungsi Livewire --}}
                <button type="button" x-on:click="$wire.deleteRole(selectedId); modalIsOpen = false"
                    class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 text-sm font-medium transition inline-flex items-center">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </x-mdal>

    <x-mdal name="delete-user">
        <div class="px-6 py-6 text-center">

            {{-- Icon Peringatan --}}
            <div
                class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-500">
                <iconify-icon icon="mingcute:warning-line" class="text-2xl"></iconify-icon>
            </div>

            <h3 class="mb-1 text-lg font-semibold text-neutral-800 dark:text-neutral-100">
                Hapus User Ini?
            </h3>

            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Tindakan ini tidak dapat dibatalkan. User akan hilang dari sistem.
            </p>

            {{-- Action Buttons --}}
            <div class="flex justify-center gap-3 border-t pt-4 border-neutral-100 dark:border-neutral-700">

                {{-- Tombol Cancel --}}
                <button type="button" x-on:click="modalIsOpen = false"
                    class="px-4 py-2 rounded-lg border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-medium transition">
                    Batal
                </button>

                {{-- Tombol Delete --}}
                {{-- Mengirim selectedId (dari Alpine) ke fungsi Livewire --}}
                <button type="button" x-on:click="$wire.deleteUser(selectedId); modalIsOpen = false"
                    class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 text-sm font-medium transition inline-flex items-center">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </x-mdal>
</div>
