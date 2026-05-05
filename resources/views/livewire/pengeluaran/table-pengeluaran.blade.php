<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Pengeluaran' }}</h6>
        <x-breadcrumb :title="$title ?? 'Pengeluaran'" />
    </div>

    <x-toast />

    @php
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $catColors = [
            'Bahan Baku' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'Gaji Karyawan' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'Listrik & Air' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'Kebersihan' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
            'Peralatan' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
            'Konsumsi' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
            'Operasional' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
            'Transport' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'Lainnya' => 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-400',
        ];
    @endphp

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-8">
        {{-- Total Filtered --}}
        <div
            class="md:col-span-4 bg-white dark:bg-neutral-800 rounded-3xl p-8 shadow-sm border border-neutral-100 dark:border-neutral-700 relative overflow-hidden">
            <h4 class="text-xs font-bold text-neutral-400 uppercase tracking-widest mb-1">Total Filtered</h4>
            <p class="text-3xl font-black text-blue-600 dark:text-blue-500 mb-4">
                Rp {{ number_format($this->totalFiltered, 0, ',', '.') }}
            </p>
            <div class="flex items-center gap-2 text-[11px] text-neutral-400">
                <iconify-icon icon="lucide:sparkles" class="text-blue-400"></iconify-icon>
                <span>Update terakhir: Hari ini</span>
            </div>
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 dark:bg-blue-900/10 rounded-full opacity-50">
            </div>
        </div>

        {{-- Total Seluruhnya (Dark) --}}
        <div
            class="md:col-span-8 bg-neutral-900 rounded-3xl p-8 shadow-xl shadow-neutral-900/20 flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h4 class="text-xs font-bold text-neutral-500 uppercase tracking-widest mb-1">Total Seluruhnya</h4>
                <p class="text-4xl font-black text-white">
                    Rp {{ number_format($this->totalAllTime, 0, ',', '.') }}
                </p>
            </div>
            <div
                class="bg-neutral-800/80 backdrop-blur-sm rounded-2xl p-4 border border-neutral-700 flex items-center gap-4 w-full md:w-64">
                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center">
                    <iconify-icon icon="lucide:trending-up" class="text-white text-xl"></iconify-icon>
                </div>
                <div>
                    <span class="text-[10px] text-neutral-500 font-bold uppercase block -mb-1">Bulan Ini</span>
                    <span class="text-sm font-black text-white">+{{ number_format($this->growthPercentage, 1) }}% <span
                            class="text-[10px] font-medium text-neutral-500 ml-1">dari bulan lalu</span></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Bar --}}
    <div class="bg-indigo-50/50 dark:bg-neutral-800/50 rounded-3xl p-4 mb-8 flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[300px]">
            <x-ui.input prefix="" placeholder="Cari pengeluaran (kategori, deskripsi, dll)..."
                wire:model.live="search" />
        </div>

        <x-dropdown align="right" width="w-48">
            <x-slot name="trigger">
                <button type="button"
                    class="flex items-center gap-2 px-6 py-3 bg-white dark:bg-neutral-900 border-0 rounded-2xl text-sm font-semibold text-neutral-700 dark:text-neutral-300 shadow-sm hover:bg-neutral-50 transition-all min-w-[140px] justify-between">
                    <span>{{ $filterMonth ? $months[$filterMonth] : 'Bulan' }}</span>
                    <iconify-icon icon="lucide:chevron-down" class="text-neutral-400"></iconify-icon>
                </button>
            </x-slot>
            <x-slot name="content">
                <div class="p-2 max-h-64 overflow-y-auto custom-scrollbar">
                    <button wire:click="$set('filterMonth', '')"
                        class="w-full text-left px-4 py-2 text-sm rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-800 {{ $filterMonth == '' ? 'bg-blue-50 text-blue-600 font-bold' : '' }}">
                        Semua Bulan
                    </button>
                    @foreach($months as $num => $name)
                        <button wire:click="$set('filterMonth', {{ $num }})"
                            class="w-full text-left px-4 py-2 text-sm rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-800 {{ $filterMonth == $num ? 'bg-blue-50 text-blue-600 font-bold' : '' }}">
                            {{ $name }}
                        </button>
                    @endforeach
                </div>
            </x-slot>
        </x-dropdown>

        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button type="button"
                    class="flex items-center gap-2 px-6 py-3 bg-white dark:bg-neutral-900 border-0 rounded-2xl text-sm font-semibold text-neutral-700 dark:text-neutral-300 shadow-sm hover:bg-neutral-50 transition-all">
                    <span>{{ $filterYear }}</span>
                    <iconify-icon icon="lucide:chevron-down" class="text-neutral-400"></iconify-icon>
                </button>
            </x-slot>
            <x-slot name="content">
                <div class="p-2 space-y-1">
                    @foreach(range(date('Y'), date('Y') - 5) as $y)
                        <button wire:click="$set('filterYear', {{ $y }})"
                            class="w-full text-left px-4 py-2 text-sm rounded-lg hover:bg-neutral-100 dark:hover:bg-neutral-800 {{ $filterYear == $y ? 'bg-blue-50 text-blue-600' : '' }}">
                            {{ $y }}
                        </button>
                    @endforeach
                </div>
            </x-slot>
        </x-dropdown>

        <button type="button"
            class="w-12 h-12 flex items-center justify-center bg-white dark:bg-neutral-900 rounded-2xl shadow-sm text-neutral-500 hover:text-blue-600 transition-all">
            <iconify-icon icon="lucide:sliders-horizontal" class="text-xl"></iconify-icon>
        </button>

        <x-ui.button-link href="/pengeluaran/create" icon="mingcute:add-circle-line">
            Tambah Pengeluaran
        </x-ui.button-link>
    </div>

    {{-- Premium Table --}}
    <x-ui.table :headers="['#', 'Tanggal', 'Kategori', 'Deskripsi', ['name' => 'Satuan', 'align' => 'center'], ['name' => 'Total', 'align' => 'right'], 'Metode', ['name' => 'Bukti', 'align' => 'center'], ['name' => 'Aksi', 'align' => 'center']]">
        @forelse ($pengeluarans as $item)
            <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/30 transition-colors group">
                <td data-label="#" class="px-6 py-6 text-xs font-bold text-neutral-300 group-hover:text-neutral-500">
                    {{ ($pengeluarans->currentPage() - 1) * $pengeluarans->perPage() + $loop->iteration }}
                </td>
                <td data-label="Tanggal" class="px-6 py-6 font-bold text-neutral-800 dark:text-neutral-200">
                    <span
                        class="block text-sm">{{ \Carbon\Carbon::parse($item->tanggal_pengeluaran)->translatedFormat('d M') }}</span>
                    <span
                        class="block text-[10px] text-neutral-400 uppercase tracking-widest">{{ \Carbon\Carbon::parse($item->tanggal_pengeluaran)->translatedFormat('Y') }}</span>
                </td>
                <td data-label="Kategori" class="px-6 py-6">
                    <span
                        class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $catColors[$item->kategori] ?? $catColors['Lainnya'] }}">
                        {{ $item->kategori ?? 'UMUM' }}
                    </span>
                </td>
                <td data-label="Deskripsi" class="px-6 py-6">
                    <p class="text-xs font-bold text-neutral-700 dark:text-neutral-300 leading-relaxed">
                        {{ $item->title }}
                    </p>
                    @if($item->catatan)
                        <p class="text-[10px] text-neutral-400 mt-1 italic">{{ Str::limit($item->catatan, 30) }}</p>
                    @endif
                </td>
                <td data-label="Satuan" class="px-6 py-6 text-center">
                    <span
                        class="text-[10px] font-black text-neutral-300 dark:text-neutral-600 uppercase tracking-widest bg-neutral-50 dark:bg-neutral-900 px-2 py-1 rounded-md">{{ $item->satuan ?? '-' }}</span>
                </td>
                <td data-label="Total" class="px-6 py-6 text-right">
                    <span class="block text-[10px] text-neutral-300 font-bold -mb-1">Rp</span>
                    <span
                        class="text-sm font-black text-neutral-800 dark:text-neutral-100">{{ number_format($item->total, 0, ',', '.') }}</span>
                </td>
                <td data-label="Metode" class="px-6 py-6">
                    @php
                        $metodeIcon = match ($item->metode_pembayaran) {
                            'Cash' => ['icon' => 'lucide:banknote', 'color' => 'text-emerald-500'],
                            'Transfer Bank' => ['icon' => 'lucide:laptop', 'color' => 'text-blue-500'],
                            'E-Wallet' => ['icon' => 'lucide:wallet', 'color' => 'text-purple-500'],
                            default => ['icon' => 'lucide:credit-card', 'color' => 'text-neutral-400']
                        };
                    @endphp
                    <div class="flex items-center gap-2">
                        <iconify-icon icon="{{ $metodeIcon['icon'] }}"
                            class="{{ $metodeIcon['color'] }} text-sm"></iconify-icon>
                        <span
                            class="text-[11px] font-bold text-neutral-500">{{ $item->metode_pembayaran ?? 'Debit Card' }}</span>
                    </div>
                </td>
                <td data-label="Bukti" class="px-6 py-6 text-center">
                    @if ($item->bukti)
                        <a href="{{ Storage::url($item->bukti) }}" target="_blank"
                            class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white transition-all">
                            <iconify-icon icon="lucide:image" class="text-sm"></iconify-icon>
                        </a>
                    @else
                        <iconify-icon icon="lucide:image"
                            class="text-neutral-200 dark:text-neutral-700 opacity-50"></iconify-icon>
                    @endif
                </td>
                <td data-label="Aksi" class="px-6 py-6">
                    <div class="flex items-center justify-center gap-2">
                        <x-ui.action-edit :href="route('pengeluaran.edit', $item->id)" wire:navigate />
                        <x-ui.action-delete
                            @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ json_encode(base64_encode($item->id)) }} })" />
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center py-20">
                    <div class="flex flex-col items-center">
                        <iconify-icon icon="lucide:folder-search" class="text-5xl text-neutral-200 mb-2"></iconify-icon>
                        <p class="text-sm font-bold text-neutral-400 tracking-wide">Tidak ada data ditemukan</p>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    @if($pengeluarans->hasPages())
        <div class="px-6 py-6 border-t border-neutral-100 dark:border-neutral-700 bg-neutral-50/30 dark:bg-neutral-900/10">
            {{ $pengeluarans->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
        </div>
    @endif


    {{-- Modal Konfirmasi Hapus --}}
    <x-mdl>
        <div class="px-6 py-2 text-center">
            <h3 class="font-black text-xl text-neutral-800 dark:text-neutral-100">Hapus Data?</h3>
            <p class="text-sm text-neutral-500 mt-2">Tindakan ini permanen dan tidak dapat dibatalkan.</p>
        </div>
        <div class="flex justify-center gap-3 p-6 pt-2">
            <button x-on:click="modalIsOpen = false"
                class="flex-1 px-6 py-3 rounded-2xl border border-neutral-200 bg-white text-sm font-bold text-neutral-600 hover:bg-neutral-50 transition-all dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-300">
                Cancel
            </button>
            <button x-on:click="$wire.deletePengeluaran(selectedId); modalIsOpen = false"
                class="flex-1 px-6 py-3 rounded-2xl bg-rose-600 text-sm font-bold text-white hover:bg-rose-700 shadow-lg shadow-rose-600/20 transition-all active:scale-95">
                Delete
            </button>
        </div>
    </x-mdl>
</div>