<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Pesanan' }}</h6>
        <x-breadcrumb :title="$title ?? 'Pesanan'" />
    </div>
    <x-toast />
    
    {{-- Summary Bar --}}
    @if (!empty($totalPerMetode))
    <div class="mb-6 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
        @foreach ($totalPerMetode as $metode => $total)
            <div class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 p-4 rounded-3xl shadow-sm">
                <p class="text-xs font-bold text-neutral-500 uppercase tracking-widest mb-1">{{ ucfirst($metode ?? 'Belum Bayar') }}</p>
                <p class="text-lg font-bold text-neutral-900 dark:text-white">Rp{{ number_format($total, 0, ',', '.') }}</p>
            </div>
        @endforeach
        <div class="bg-blue-600 p-4 rounded-3xl shadow-lg shadow-blue-500/30 col-span-2 md:col-span-1 lg:col-span-1 ml-auto w-full md:w-auto min-w-[200px]">
            <p class="text-xs font-bold text-blue-100 uppercase tracking-widest mb-1">Total Omset</p>
            <p class="text-lg font-bold text-white">Rp{{ number_format($totalOmset, 0, ',', '.') }}</p>
        </div>
    </div>
    @endif

    {{-- Header Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div class="flex gap-2">
            <x-droppage perPage="{{ $perPage }}" />
            <div class="sm:w-[300px]">
                <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Cari pesanan..." class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
            </div>
        </div>
        <div class="flex gap-2">
            <x-ui.button-link href="/order/create" icon="mingcute:add-circle-line">
                Pesan Menu
            </x-ui.button-link>
        </div>
    </div>

    <x-ui.table :headers="[
        ['name' => '#', 'align' => 'center'],
        'Kode',
        'Nama Pelanggan',
        ['name' => 'Status', 'align' => 'center'],
        'Metode',
        'Total',
        'Kasir',
        'Waktu',
        ['name' => 'Action', 'align' => 'center']
    ]">
        @forelse ($orders as $item)
            <tr wire:key="{{ $item->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition">
                <td class="px-6 py-4 text-center text-sm text-neutral-500">
                    {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
                </td>
                <td class="px-6 py-4">
                    <span class="font-mono text-sm font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2 py-1 rounded-lg">#{{ $item->kode }}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="font-semibold text-neutral-800 dark:text-neutral-200">{{ $item->nama ?: '-' }}</span>
                </td>
                <td class="px-6 py-4 text-center">
                    @php
                        $statusClasses = [
                            'selesai' => 'bg-green-100 text-green-700 border-green-200',
                            'diproses' => 'bg-amber-100 text-amber-700 border-amber-200',
                            'batal' => 'bg-rose-100 text-rose-700 border-rose-200',
                        ];
                        $statusClass = $statusClasses[$item->status] ?? 'bg-neutral-100 text-neutral-700 border-neutral-200';
                    @endphp
                    <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border {{ $statusClass }}">
                        {{ ucwords(str_replace('_', ' ', $item->status)) }}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $item->metode_pembayaran ? ucwords($item->metode_pembayaran) : 'Belum Bayar' }}
                </td>
                <td class="px-6 py-4">
                    <span class="font-bold text-neutral-900 dark:text-white">Rp{{ number_format($item->total - $item->discount_value, 0, ',', '.') }}</span>
                </td>
                <td class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $item->user->name }}
                </td>
                <td class="px-6 py-4 text-sm text-neutral-500 dark:text-neutral-400">
                    {{ $item->created_at->format('H:i') }}
                </td>
                <td class="px-6 py-4 text-center">
                    <div class="flex justify-center gap-2">
                        @role('admin')
                            @if ($item->status == 'diproses')
                                <button wire:click="saji('{{ base64_encode($item->id) }}')"
                                    wire:loading.attr="disabled" wire:target="saji('{{ base64_encode($item->id) }}')"
                                    class="w-8 h-8 rounded-xl bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center hover:bg-green-600 hover:text-white transition-all"
                                    title="Tandai Selesai">
                                    <iconify-icon icon="mingcute:check-line"></iconify-icon>
                                </button>
                            @endif

                            <a href="{{ route('struk.print', base64_encode($item->id)) }}"
                                class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all"
                                title="Print Struk">
                                <iconify-icon icon="lucide:printer" class="text-xs"></iconify-icon>
                            </a>

                            <x-ui.action-edit href="/order/{{ base64_encode($item->id) }}/edit" wire:navigate />

                            <x-ui.action-delete @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id)) }} })" />
                        @endrole

                        @unlessrole('admin')
                            @if ($item->status !== 'selesai')
                                @if ($item->status == 'diproses')
                                    <button wire:click="saji('{{ base64_encode($item->id) }}')"
                                        class="w-8 h-8 rounded-xl bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center hover:bg-green-600 hover:text-white transition-all">
                                        <iconify-icon icon="mingcute:check-line"></iconify-icon>
                                    </button>
                                @endif
                                <x-ui.action-edit href="/order/{{ base64_encode($item->id) }}/edit" wire:navigate />
                            @endif

                            <a href="{{ route('struk.print', base64_encode($item->id)) }}"
                                class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all"
                                title="Print Struk">
                                <iconify-icon icon="lucide:printer" class="text-xs"></iconify-icon>
                            </a>
                        @endunlessrole
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center py-12 text-neutral-500">
                    <div class="flex flex-col items-center justify-center gap-3">
                        <iconify-icon icon="mingcute:ghost-line" class="text-4xl"></iconify-icon>
                        <span class="text-sm">Tidak ada pesanan ditemukan.</span>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-4">
        {{ $orders->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    <x-mdal name="confirm-delete">
        <div class="px-6 py-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-rose-100 text-rose-600 shadow-sm border border-rose-200">
                <iconify-icon icon="lucide:alert-triangle" class="text-2xl"></iconify-icon>
            </div>

            <h3 class="mb-1 text-lg font-bold text-neutral-900 dark:text-neutral-100">Hapus Pesanan Ini?</h3>
            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Tindakan ini tidak dapat dibatalkan. Data pesanan akan dihapus dari sistem.
            </p>

            <div class="flex justify-center gap-3 border-t pt-6 border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'confirm-delete' })"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                    Batal
                </button>

                <x-ui.button type="button" color="danger" @click="$wire.delPesanan(selectedId); $dispatch('close-modal', { name: 'confirm-delete' })" class="!px-5 !py-2.5">
                    Ya, Hapus
                </x-ui.button>
            </div>
        </div>
    </x-mdal>
</div>
