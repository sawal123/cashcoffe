<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Manajemen Aset' }}</h6>
        <x-breadcrumb :title="$title ?? 'Manajemen Aset'" />
    </div>
    <x-toast />

    {{-- Header Controls --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div class="flex flex-wrap items-center gap-2">
            <x-droppage perPage="{{ $perPage }}" />
            <div class="w-full lg:min-w-[300px] flex-none">
                <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Cari aset (nama, kode, kategori)..."
                    class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700"
                    prefix='<iconify-icon icon="lucide:search" class="text-xl"></iconify-icon>' />
            </div>
        </div>

        @hasrole('superadmin')
            <div class="flex justify-end gap-2">
                <x-ui.button wire:click="openModal" icon="mingcute:add-circle-line">
                    Tambah Aset
                </x-ui.button>
            </div>
        @endhasrole
    </div>

    <x-ui.table :headers="[
        ['name' => '#', 'align' => 'center'],
        'Aset',
        ['name' => 'Qty', 'align' => 'center'],
        'Cabang',
        'Kategori',
        ['name' => 'Kondisi', 'align' => 'center'],
        'Tgl Beli',
        'Harga Beli',
        ['name' => 'Action', 'align' => 'center'],
    ]">
        @forelse ($assets as $item)
            <tr wire:key="asset-{{ $item->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition">
                <td data-label="#" class="px-4 sm:px-6 py-4 text-center text-sm text-neutral-500">
                    {{ ($assets->currentPage() - 1) * $assets->perPage() + $loop->iteration }}
                </td>
                <td data-label="Aset" class="px-4 sm:px-6 py-4">
                    <div>
                        <p class="font-bold text-neutral-800 dark:text-neutral-200">{{ $item->nama_aset }}</p>
                        <p class="text-[10px] text-blue-600 font-mono font-bold uppercase tracking-widest">
                            {{ $item->kode_aset }}
                        </p>
                    </div>
                </td>
                <td data-label="Qty" class="px-4 sm:px-6 py-4 text-center">
                    <span class="font-bold text-neutral-800 dark:text-neutral-200">{{ $item->qty }}</span>
                </td>
                <td data-label="Cabang" class="px-4 sm:px-6 py-4">
                    <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">
                        {{ $item->branch->nama_cabang ?? '-' }}
                    </span>
                </td>
                <td data-label="Kategori" class="px-4 sm:px-6 py-4">
                    <span class="text-xs font-semibold px-2 py-1 rounded-lg bg-neutral-100 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700">
                        {{ $item->kategori }}
                    </span>
                </td>
                <td data-label="Kondisi" class="px-4 sm:px-6 py-4 text-center">
                    @php
                        $kondisiClasses = [
                            'Baik' => 'bg-green-100 text-green-700 border-green-200',
                            'Rusak Ringan' => 'bg-amber-100 text-amber-700 border-amber-200',
                            'Rusak Berat' => 'bg-rose-100 text-rose-700 border-rose-200',
                            'Dalam Perbaikan' => 'bg-blue-100 text-blue-700 border-blue-200',
                        ];
                        $kondisiClass = $kondisiClasses[$item->kondisi] ?? 'bg-neutral-100 text-neutral-700 border-neutral-200';
                    @endphp
                    <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border {{ $kondisiClass }}">
                        {{ $item->kondisi }}
                    </span>
                </td>
                <td data-label="Tgl Beli" class="px-4 sm:px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400">
                    {{ $item->tanggal_pembelian->format('d/m/Y') }}
                </td>
                <td data-label="Harga" class="px-4 sm:px-6 py-4">
                    <span class="font-bold text-neutral-900 dark:text-white">
                        Rp{{ number_format($item->harga_beli, 0, ',', '.') }}
                    </span>
                </td>
                <td data-label="Aksi" class="px-4 sm:px-6 py-4 text-center">
                    <div class="flex justify-center gap-2">
                        <x-ui.action-edit wire:click="editAsset({{ $item->id }})" />
                        @hasrole('superadmin')
                            <x-ui.action-delete @click="$dispatch('open-modal', { name: 'confirm-delete', id: {{ $item->id }} })" />
                        @endhasrole
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center py-12 text-neutral-500">
                    <div class="flex flex-col items-center justify-center gap-3">
                        <iconify-icon icon="mingcute:ghost-line" class="text-4xl"></iconify-icon>
                        <span class="text-sm">Tidak ada data aset ditemukan.</span>
                    </div>
                </td>
            </tr>
        @endforelse
    </x-ui.table>

    <div class="mt-4">
        {{ $assets->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
    </div>

    {{-- Modal Add/Edit --}}
    <x-mdal name="asset-modal" :title="$isEdit ? 'Edit Aset' : 'Tambah Aset Baru'" maxWidth="2xl">
        <form wire:submit.prevent="saveAsset" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Branch (Hanya Superadmin) --}}
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-xs font-black uppercase tracking-widest text-neutral-400 mb-2">Cabang</label>
                    @if(auth()->user()->hasRole('superadmin'))
                        <select wire:model="branch_id" class="w-full rounded-2xl border-neutral-200 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 focus:border-blue-500 focus:ring-blue-500 transition-all">
                            <option value="">Pilih Cabang</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->nama_cabang }}</option>
                            @endforeach
                        </select>
                    @else
                        <div class="p-3 bg-neutral-100 dark:bg-neutral-800 rounded-2xl text-sm font-bold text-neutral-600 dark:text-neutral-400 border border-neutral-200 dark:border-neutral-700">
                            {{ auth()->user()->branch->nama_cabang ?? '-' }}
                        </div>
                        <input type="hidden" wire:model="branch_id">
                    @endif
                    @error('branch_id') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Kode Aset --}}
                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-neutral-400 mb-2">Kode Aset</label>
                    <x-ui.input wire:model="kode_aset" placeholder="Contoh: AST-JKT-001" :disabled="auth()->user()->hasRole('manager') && $isEdit" />
                    @error('kode_aset') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Nama Aset --}}
                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-neutral-400 mb-2">Nama Aset</label>
                    <x-ui.input wire:model="nama_aset" placeholder="Nama Barang" :disabled="auth()->user()->hasRole('manager') && $isEdit" />
                    @error('nama_aset') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Qty --}}
                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-neutral-400 mb-2">Jumlah (Qty)</label>
                    <x-ui.input type="number" wire:model="qty" placeholder="1" :disabled="auth()->user()->hasRole('manager') && $isEdit" />
                    @error('qty') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Kategori --}}
                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-neutral-400 mb-2">Kategori</label>
                    <select wire:model="kategori" :disabled="auth()->user()->hasRole('manager') && $isEdit" class="w-full rounded-2xl border-neutral-200 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 focus:border-blue-500 focus:ring-blue-500 transition-all">
                        <option value="">Pilih Kategori</option>
                        <option value="Elektronik">Elektronik</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Peralatan Dapur">Peralatan Dapur</option>
                        <option value="IT">IT</option>
                    </select>
                    @error('kategori') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Kondisi --}}
                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-neutral-400 mb-2">Kondisi</label>
                    <select wire:model="kondisi" class="w-full rounded-2xl border-neutral-200 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 focus:border-blue-500 focus:ring-blue-500 transition-all">
                        <option value="Baik">Baik</option>
                        <option value="Rusak Ringan">Rusak Ringan</option>
                        <option value="Rusak Berat">Rusak Berat</option>
                        <option value="Dalam Perbaikan">Dalam Perbaikan</option>
                    </select>
                    @error('kondisi') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Tanggal Pembelian --}}
                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-neutral-400 mb-2">Tanggal Pembelian</label>
                    <x-ui.input type="date" wire:model="tanggal_pembelian" :disabled="auth()->user()->hasRole('manager') && $isEdit" />
                    @error('tanggal_pembelian') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Harga Beli --}}
                <div>
                    <label class="block text-xs font-black uppercase tracking-widest text-neutral-400 mb-2">Harga Beli</label>
                    <x-ui.input type="number" wire:model="harga_beli" placeholder="0" :disabled="auth()->user()->hasRole('manager') && $isEdit" />
                    @error('harga_beli') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Keterangan --}}
                <div class="col-span-1 md:col-span-2">
                    <label class="block text-xs font-black uppercase tracking-widest text-neutral-400 mb-2">Keterangan</label>
                    <textarea wire:model="keterangan" rows="3" class="w-full rounded-2xl border-neutral-200 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 focus:border-blue-500 focus:ring-blue-500 transition-all" placeholder="Catatan tambahan..."></textarea>
                    @error('keterangan') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-3 border-t pt-6 border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'asset-modal' })"
                    class="px-6 py-2.5 rounded-2xl border border-neutral-300 dark:border-neutral-600 bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-700 text-sm font-bold transition">
                    Batal
                </button>
                <x-ui.button type="submit" wire:loading.attr="disabled">
                    <iconify-icon icon="mingcute:save-line" class="text-lg mr-2" wire:loading.remove></iconify-icon>
                    <iconify-icon icon="line-md:loading-twotone-loop" class="text-lg mr-2" wire:loading></iconify-icon>
                    {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Aset' }}
                </x-ui.button>
            </div>
        </form>
    </x-mdal>

    {{-- Modal Delete --}}
    <x-mdal name="confirm-delete">
        <div class="px-6 py-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-rose-100 text-rose-600 shadow-sm border border-rose-200">
                <iconify-icon icon="lucide:alert-triangle" class="text-2xl"></iconify-icon>
            </div>

            <h3 class="mb-1 text-lg font-bold text-neutral-900 dark:text-neutral-100">Hapus Aset Ini?</h3>
            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Data aset akan dihapus secara permanen (Soft Delete).
            </p>

            <div class="flex justify-center gap-3 border-t pt-6 border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'confirm-delete' })"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                    Batal
                </button>
                <x-ui.button type="button" color="danger" @click="$wire.deleteAsset(selectedId); $dispatch('close-modal', { name: 'confirm-delete' })" class="!px-5 !py-2.5">
                    Ya, Hapus
                </x-ui.button>
            </div>
        </div>
    </x-mdal>
</div>
