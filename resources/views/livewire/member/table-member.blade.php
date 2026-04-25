<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Member' }}</h6>
        <x-breadcrumb :title="$title ?? 'Member'" />
    </div>
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
                        <x-ui.action-detail wire:click="viewMemberDetails('{{ base64_encode($item->id) }}')" /> 
                        <x-ui.action-edit href="/member/{{ base64_encode($item->id) }}/edit" wire:navigate />
                        @role('superadmin')
                            <x-ui.action-delete @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id)) }} })" />
                        @endrole
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

    {{-- Modal Detail Member --}}
    <x-mdal name="detail-member-modal">
        <div class="px-6 py-6 border-b border-neutral-100 dark:border-neutral-700 flex justify-between items-center bg-neutral-50 rounded-t-[2.5rem]">
            <h3 class="text-xl font-black text-neutral-900 dark:text-neutral-100 flex items-center gap-2">
                <iconify-icon icon="mingcute:user-star-line" class="text-blue-600 text-2xl"></iconify-icon>
                Detail & Poin Member
            </h3>
            <button type="button" x-on:click="$dispatch('close-modal', { name: 'detail-member-modal' })" class="text-neutral-400 hover:text-rose-500 transition">
                <iconify-icon icon="mingcute:close-line" class="text-2xl"></iconify-icon>
            </button>
        </div>

        <div class="p-6 max-h-[60vh] overflow-y-auto">
            @if($selectedMemberDetail)
                <!-- Stats Row -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-blue-50 dark:bg-neutral-800 p-4 rounded-2xl border border-blue-100 dark:border-neutral-700 flex flex-col items-center justify-center">
                        <span class="text-xs font-bold text-blue-500 uppercase tracking-widest mb-1">Total Poin</span>
                        <span class="text-3xl font-black text-blue-700">{{ $selectedMemberDetail->points }}</span>
                    </div>
                    <div class="bg-green-50 dark:bg-neutral-800 p-4 rounded-2xl border border-green-100 dark:border-neutral-700 flex flex-col items-center justify-center text-center">
                        <span class="text-xs font-bold text-green-500 uppercase tracking-widest mb-1">Pengeluaran</span>
                        <span class="text-lg font-black text-green-700">Rp {{ number_format($selectedMemberDetail->total_pengeluaran, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="mb-4">
                    <h4 class="text-sm font-bold text-neutral-800 dark:text-neutral-200 uppercase tracking-widest border-b pb-2 mb-3 border-neutral-100">10 Transaksi Terakhir</h4>
                    @if(count($memberTransactions) > 0)
                        <div class="space-y-3">
                            @foreach($memberTransactions as $trx)
                                <div class="flex flex-col p-3 rounded-xl border border-neutral-100 bg-white shadow-sm gap-2">
                                    <div class="flex justify-between items-center border-b border-neutral-50 pb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-bold text-neutral-400 bg-neutral-100 px-2 rounded">{{ \Carbon\Carbon::parse($trx->created_at)->format('d M Y') }}</span>
                                            <span class="text-xs font-bold text-blue-600">ID: {{ $trx->kode }}</span>
                                        </div>
                                        <span class="text-xs font-black text-emerald-600">Rp {{ number_format($trx->total - $trx->discount_value, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="text-[11px] text-neutral-500">
                                        {{ $trx->items->pluck('menu.nama_menu')->join(', ') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-6 text-center text-neutral-400 italic bg-neutral-50 rounded-xl border border-neutral-100 shadow-inner">Belum ada riwayat transaksi.</div>
                    @endif
                </div>
            @else
                <div class="p-10 text-center"><iconify-icon icon="line-md:loading-twotone-loop" class="text-3xl text-neutral-400"></iconify-icon></div>
            @endif
        </div>
    </x-mdal>
</div>
