<div>
    <x-toast />
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div class="flex gap-2">
            <x-droppage perPage="{{ $perPage }}" />
            <div class="sm:w-[300px]">
                <x-ui.input wire:model.live="search" placeholder="Cari Member..." class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
            </div>
        </div>
        <div class="flex gap-2">
            <x-ui.button-link href="/member/create" icon="mingcute:add-circle-line">
                Tambah Member
            </x-ui.button-link>
        </div>
    </div>

    <x-ui.table :headers="[
        ['name' => '#', 'align' => 'center'],
        'Nama',
        'Email',
        'Phone',
        'Alamat',
        ['name' => 'Action', 'align' => 'center']
    ]">
        @forelse ($members as $item)
            <tr wire:key="{{ $item->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition">
                <td class="px-6 py-4 text-center text-sm text-neutral-500">
                    {{ ($members->currentPage() - 1) * $members->perPage() + $loop->iteration }}
                </td>
                <td class="px-6 py-4">
                    <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ $item->user->name ?? '-' }}</span>
                </td>
                <td class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $item->user->email ?? '-' }}
                </td>
                <td class="px-6 py-4 text-sm font-medium text-neutral-800 dark:text-neutral-200">
                    {{ $item->phone }}
                </td>
                <td class="px-6 py-4 text-sm text-neutral-500 dark:text-neutral-400">
                    {{ \Illuminate\Support\Str::limit($item->address, 30, '...') }}
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex justify-center gap-2">
                        <x-ui.action-edit href="/member/{{ base64_encode($item->id) }}/edit" wire:navigate />
                        <x-ui.action-delete @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id)) }} })" />
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-12 text-neutral-500">
                    <div class="flex flex-col items-center justify-center gap-3">
                        <iconify-icon icon="mingcute:ghost-line" class="text-4xl"></iconify-icon>
                        <span class="text-sm">Tidak ada member ditemukan.</span>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-4">
        {{ $members->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    <x-mdal name="confirm-delete">
        <div class="px-6 py-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-rose-100 text-rose-600 shadow-sm border border-rose-200">
                <iconify-icon icon="lucide:alert-triangle" class="text-2xl"></iconify-icon>
            </div>

            <h3 class="mb-1 text-lg font-bold text-neutral-900 dark:text-neutral-100">Hapus Member?</h3>
            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Tindakan ini tidak dapat dibatalkan. Data member akan terhapus dari sistem.
            </p>

            <div class="flex justify-center gap-3 border-t pt-6 border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'confirm-delete' })"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                    Batal
                </button>

                <x-ui.button type="button" color="danger" @click="$wire.deleteMember(selectedId); $dispatch('close-modal', { name: 'confirm-delete' })" class="!px-5 !py-2.5">
                    Ya, Hapus
                </x-ui.button>
            </div>
        </div>
    </x-mdal>
</div>
