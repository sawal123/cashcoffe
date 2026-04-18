<div>
    <x-toast />

    @php
        $submit = $pengeluaranId ? 'update(' . $pengeluaranId . ')' : 'simpan';
        $buttonLabel = $pengeluaranId ? 'Update' : 'Simpan';
        $methods = [
            ['id' => 'Cash', 'label' => 'Tunai', 'icon' => 'lucide:banknote'],
            ['id' => 'Transfer Bank', 'label' => 'Transfer', 'icon' => 'lucide:laptop'],
            ['id' => 'E-Wallet', 'label' => 'E-Wallet', 'icon' => 'lucide:wallet'],
        ];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {{-- Left Column: Main Form --}}
        <div class="lg:col-span-8">
            <!-- <div class="bg-white dark:bg-neutral-800 rounded-3xl shadow-sm border-t-4 border-blue-600 overflow-hidden"> -->
            <div class="">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-neutral-800 dark:text-neutral-100">Detail Transaksi</h2>
                    <p class="text-neutral-500 dark:text-neutral-400">Lengkapi informasi pengeluaran Anda dengan
                        presisi.</p>
                </div>

                <form wire:submit.prevent="{{ $submit }}" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Tanggal --}}
                        <x-ui.input type="date" label="Tanggal Pengeluaran" wire:model="tanggal_pengeluaran" />

                        {{-- Kategori --}}
                        <x-ui.select label="Kategori" wire:model="kategori">
                            <option value="">Pilih Kategori</option>
                            <option value="Bahan Baku">Bahan Baku</option>
                            <option value="Gaji Karyawan">Gaji Karyawan</option>
                            <option value="Listrik & Air">Listrik & Air</option>
                            <option value="Kebersihan">Kebersihan</option>
                            <option value="Peralatan">Peralatan</option>
                            <option value="Lainnya">Lainnya</option>
                        </x-ui.select>
                    </div>

                    {{-- Nama Item --}}
                    <x-ui.input label="Nama Item / Keperluan" wire:model="title"
                        placeholder="Contoh: Pembelian Bahan Baku Kopi Arabica" />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Satuan --}}
                        <x-ui.input label="Satuan" wire:model="satuan" placeholder="Kg, Meter, Pcs, dll" />

                        {{-- Total --}}
                        <x-ui.input type="number" step="100" label="Total (Rp)" wire:model="total" prefix="Rp"
                            placeholder="0" />
                    </div>

                    {{-- Metode Pembayaran --}}
                    <div>
                        <label class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-2 block">
                            Metode Pembayaran
                        </label>
                        <div class="grid grid-cols-3 gap-4">
                            @foreach ($methods as $method)
                                <button type="button" wire:click="setMetode('{{ $method['id'] }}')"
                                    class="flex flex-col items-center justify-center p-4 rounded-2xl border-2 transition-all {{ $metode_pembayaran === $method['id'] ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'border-neutral-100 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-900 text-neutral-500 hover:border-blue-200' }}">
                                    <iconify-icon icon="{{ $method['icon'] }}" class="text-2xl mb-2"></iconify-icon>
                                    <span class="text-xs font-bold">{{ $method['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                        @error('metode_pembayaran')
                            <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <label
                            class="text-sm font-semibold text-neutral-600 dark:text-neutral-400 mb-2 block text-left">
                            <iconify-icon icon="lucide:align-left" class="inline-block mr-1"></iconify-icon> Catatan
                        </label>
                        <textarea wire:model="catatan" rows="4"
                            class="w-full bg-neutral-50 dark:bg-neutral-900 border-0 rounded-2xl px-4 py-3 placeholder:text-neutral-400 focus:ring-2 focus:ring-blue-500 text-neutral-800 dark:text-neutral-200"
                            placeholder="Tambahkan keterangan tambahan jika ada..."></textarea>
                    </div>

                    {{-- Footer Buttons --}}
                    <div class="flex items-center justify-end gap-4 pt-4">
                        <button type="button" onclick="history.back()"
                            class="px-8 py-3 bg-neutral-100 dark:bg-neutral-700 text-neutral-600 dark:text-neutral-300 rounded-2xl font-bold hover:bg-neutral-200 transition-all">
                            Batal
                        </button>
                        <x-ui.button type="submit" color="blue">
                            {{ $buttonLabel }} Pengeluaran
                        </x-ui.button>
                    </div>
                </form>
            </div>
            <!-- </div> -->
        </div>

        {{-- Right Column: Sidebar --}}
        <div class="lg:col-span-4 space-y-6">
            {{-- Bukti Pembayaran --}}
            <div
                class="bg-white dark:bg-neutral-800 rounded-3xl p-6 shadow-sm border border-neutral-100 dark:border-neutral-700">
                <div class="flex items-center gap-2 mb-4">
                    <iconify-icon icon="lucide:cloud-upload" class="text-blue-600 text-xl"></iconify-icon>
                    <h3 class="font-bold text-neutral-800 dark:text-neutral-100">Bukti Pembayaran</h3>
                </div>
                <p class="text-xs text-neutral-500 mb-4">Upload foto nota atau struk transfer (Opsional)</p>

                <div class="relative group">
                    <input type="file" wire:model="bukti"
                        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                    <div
                        class="border-2 border-dashed border-neutral-200 dark:border-neutral-700 rounded-2xl p-8 flex flex-col items-center justify-center text-center transition-all group-hover:border-blue-400 group-hover:bg-blue-50/50 dark:group-hover:bg-blue-900/10">
                        @if ($bukti && !is_string($bukti))
                            <iconify-icon icon="lucide:check-circle" class="text-4xl text-green-500 mb-2"></iconify-icon>
                            <span class="text-sm font-bold text-neutral-700 dark:text-neutral-300">File Terpilih</span>
                        @elseif (is_string($bukti))
                            <iconify-icon icon="lucide:image" class="text-4xl text-blue-500 mb-2"></iconify-icon>
                            <a href="{{ asset('storage/' . $bukti) }}" target="_blank"
                                class="text-sm font-bold text-blue-600 hover:underline">Lihat Bukti Lama</a>
                        @else
                            <iconify-icon icon="lucide:image-plus" class="text-4xl text-neutral-300 mb-2"></iconify-icon>
                            <span class="text-sm font-bold text-neutral-700 dark:text-neutral-300">Klik untuk Unggah</span>
                            <span class="text-[10px] text-neutral-400 mt-1 uppercase">PNG, JPG up to 2MB</span>
                        @endif
                    </div>
                </div>
                @error('bukti')
                    <span class="text-danger-600 text-xs mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            {{-- Ringkasan Anggaran --}}
            <div class="bg-blue-600 rounded-3xl p-6 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="text-lg font-bold mb-4">Ringkasan Anggaran</h3>
                    <div class="space-y-2 mb-6">
                        <div class="flex justify-between items-end">
                            <span class="text-xs text-blue-100 opacity-80 leading-tight">Sisa Anggaran Bulan Ini</span>
                            <span class="text-lg font-bold leading-tight">Rp
                                {{ number_format($this->budgetSummary['sisa'], 0, ',', '.') }}</span>
                        </div>
                        <div class="w-full bg-blue-700/50 rounded-full h-2">
                            <div class="bg-white rounded-full h-2" style="width: {{ $this->budgetSummary['persen'] }}%">
                            </div>
                        </div>
                    </div>
                    <p class="text-[11px] italic opacity-80 leading-relaxed font-medium">
                        "Pengeluaran yang tercatat dengan baik membantu pertumbuhan bisnis yang lebih sehat."
                    </p>
                </div>
                <iconify-icon icon="lucide:wallet"
                    class="absolute -right-4 -bottom-4 text-8xl text-white opacity-10"></iconify-icon>
            </div>

            {{-- Terakhir Ditambahkan --}}
            <div>
                <h3 class="text-xs font-bold text-neutral-400 uppercase tracking-widest mb-4">Terakhir Ditambahkan</h3>
                <div class="space-y-3">
                    @forelse ($this->recentPengeluarans as $recent)
                        <div
                            class="bg-white dark:bg-neutral-800 rounded-2xl p-4 shadow-sm border border-neutral-50 dark:border-neutral-700 flex items-center gap-4">
                            <div
                                class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center">
                                @php
                                    $icon = match ($recent->kategori) {
                                        'Listrik & Air' => 'lucide:zap',
                                        'Bahan Baku' => 'lucide:shopping-cart',
                                        'Lainnya' => 'lucide:box',
                                        default => 'lucide:receipt'
                                    };
                                @endphp
                                <iconify-icon icon="{{ $icon }}"></iconify-icon>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-sm text-neutral-800 dark:text-neutral-100 truncate">
                                    {{ $recent->title }}
                                </h4>
                                <p class="text-[10px] text-neutral-400">
                                    {{ \Carbon\Carbon::parse($recent->tanggal_pengeluaran)->translatedFormat('d M Y') }} •
                                    {{ $recent->metode_pembayaran }}
                                </p>
                            </div>
                            <div class="text-sm font-bold text-neutral-800 dark:text-neutral-100">
                                Rp {{ number_format($recent->total, 0, ',', '.') }}
                            </div>
                        </div>
                    @empty
                        <div
                            class="text-center py-8 bg-neutral-50 dark:bg-neutral-900 rounded-2xl border-2 border-dashed border-neutral-100 dark:border-neutral-800">
                            <p class="text-xs text-neutral-400">Belum ada data terbaru</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>