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
                <td data-label="#" class="px-6 py-4 text-center text-sm text-neutral-500">
                    {{ ($members->currentPage() - 1) * $members->perPage() + $loop->iteration }}
                </td>
                <td data-label="Nama" class="px-6 py-4">
                    <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ $item->user->name ?? '-' }}</span>
                </td>
                <td data-label="Email" class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $item->user->email ?? '-' }}
                </td>
                <td data-label="Phone" class="px-6 py-4 text-sm font-medium text-neutral-800 dark:text-neutral-200">
                    {{ $item->phone }}
                </td>
                <td data-label="Alamat" class="px-6 py-4 text-sm text-neutral-500 dark:text-neutral-400">
                    {{ \Illuminate\Support\Str::limit($item->address, 30, '...') }}
                </td>
                <td data-label="Aksi" class="px-6 py-4 text-center">
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
    <style>
        .member-detail-modal-overlay {
            background: rgb(2 6 23 / 82%);
            backdrop-filter: blur(10px);
            overflow: hidden;
        }

        .member-detail-modal-panel {
            max-height: 82vh;
            overflow: hidden;
            background: #202c3d !important;
            border-color: #3b4759 !important;
            color: #f8fafc;
        }

        .member-detail-scroll {
            overscroll-behavior: contain;
            scrollbar-color: #7f8da0 transparent;
            scrollbar-width: thin;
        }

        .member-detail-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .member-detail-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .member-detail-scroll::-webkit-scrollbar-thumb {
            background: #7f8da0;
            border: 1px solid transparent;
            background-clip: padding-box;
            border-radius: 999px;
        }

        .member-detail-scroll::-webkit-scrollbar-thumb:hover {
            background: #a8b3c2;
            background-clip: padding-box;
        }

        @media (max-width: 640px) {
            .member-detail-modal-panel {
                max-height: 88vh;
            }

            .member-detail-scroll {
                max-height: calc(88vh - 78px);
            }
        }
    </style>

    <x-mdal name="detail-member-modal" :show-close="false" max-width="max-w-xl"
        panel-class="member-detail-modal-panel" overlay-class="member-detail-modal-overlay">
        <div class="flex items-center justify-between gap-4 border-b border-slate-700 bg-[#202c3d] px-7 py-5">
            <h3 class="flex min-w-0 items-center gap-3 text-xl font-black tracking-tight text-slate-100 sm:text-2xl">
                <iconify-icon icon="mingcute:user-star-line" class="shrink-0 text-2xl text-blue-500"></iconify-icon>
                <span class="truncate">Detail &amp; Poin Member</span>
            </h3>
            <button type="button" x-on:click="$dispatch('close-modal', { name: 'detail-member-modal' })"
                aria-label="Tutup detail member"
                class="shrink-0 text-slate-400 transition hover:text-white">
                <iconify-icon icon="mingcute:close-line" class="text-3xl"></iconify-icon>
            </button>
        </div>

        <div class="member-detail-scroll max-h-[calc(82vh-78px)] overflow-y-auto bg-[#202c3d] px-7 py-7 sm:px-8">
            @if($selectedMemberDetail)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="flex min-h-[110px] flex-col items-center justify-center rounded-2xl border border-slate-600 bg-[#202c3d] px-4 py-5 text-center">
                        <span class="mb-1 text-xs font-extrabold uppercase tracking-[0.12em] text-slate-100">Total Poin</span>
                        <span class="text-4xl font-black leading-none text-blue-300">{{ $selectedMemberDetail->points }}</span>
                    </div>
                    <div class="flex min-h-[110px] flex-col items-center justify-center rounded-2xl border border-slate-600 bg-[#202c3d] px-4 py-5 text-center">
                        <span class="mb-1 text-xs font-extrabold uppercase tracking-[0.12em] text-slate-100">Pengeluaran</span>
                        <span class="text-xl font-black leading-none text-emerald-300">Rp {{ number_format($selectedMemberDetail->total_pengeluaran, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="mt-8">
                    <h4 class="mb-3 border-b border-slate-200 pb-3 text-base font-extrabold uppercase tracking-[0.08em] text-slate-100">
                        10 Transaksi Terakhir
                    </h4>

                    @if(count($memberTransactions) > 0)
                        <div class="space-y-4">
                            @foreach($memberTransactions as $trx)
                                <div class="rounded-2xl border border-slate-600 bg-[#3b4759] px-4 py-4 shadow-sm">
                                    <div class="flex items-center justify-between gap-3 border-b border-slate-500 pb-3">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <span class="shrink-0 rounded bg-slate-500/70 px-2 py-0.5 text-[11px] font-extrabold text-slate-100">
                                                {{ \Carbon\Carbon::parse($trx->created_at)->format('d M Y') }}
                                            </span>
                                            <span class="truncate text-sm font-extrabold text-blue-300">ID: {{ $trx->kode }}</span>
                                        </div>
                                        <span class="shrink-0 text-sm font-black text-emerald-300">
                                            Rp {{ number_format($trx->total - $trx->discount_value, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="pt-3 text-sm font-medium text-slate-100">
                                        {{ $trx->items->pluck('menu.nama_menu')->join(', ') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-2xl border border-slate-600 bg-[#3b4759] p-8 text-center text-sm italic text-slate-300">
                            Belum ada riwayat transaksi.
                        </div>
                    @endif
                </div>
            @else
                <div class="p-10 text-center">
                    <iconify-icon icon="line-md:loading-twotone-loop" class="text-3xl text-slate-300"></iconify-icon>
                </div>
            @endif
        </div>
    </x-mdal>
</div>
