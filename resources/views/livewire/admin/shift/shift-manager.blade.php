<div class="p-6 min-h-screen">
    <!-- Header -->
    <div
        class="flex flex-col md:flex-row md:items-center justify-between mb-8 border-b border-neutral-200 dark:border-neutral-700 pb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Master Data Shift</h1>
            <p class="text-neutral-500 dark:text-neutral-400 mt-1">Kelola daftar shift kerja, jam operasional, dan
                aturan denda keterlambatan.</p>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div
            class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 rounded-xl border border-emerald-200 dark:border-emerald-800/50 flex items-center gap-3 shadow-sm">
            <i class="ri-checkbox-circle-line text-lg leading-none"></i>
            <span class="font-medium text-sm">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Form Shift -->
        <div class="lg:col-span-4">
            <div
                class="bg-white dark:bg-neutral-800 p-6 rounded-2xl shadow-sm border border-neutral-200 dark:border-neutral-700 sticky top-24">
                <h2 class="text-lg font-bold text-neutral-900 dark:text-neutral-100 mb-6 flex items-center gap-2">
                    <i class="{{ $isEdit ? 'ri-pencil-line' : 'ri-add-circle-line' }} text-primary text-xl leading-none"></i>
                    {{ $isEdit ? 'Edit Shift' : 'Tambah Shift Baru' }}
                </h2>

                <form wire:submit.prevent="saveShift" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Nama
                            Shift</label>
                        <input type="text" wire:model="nama_shift" placeholder="Contoh: Pagi, Siang, Full Day"
                            class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all"
                            required>
                        @error('nama_shift') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Jam
                                Masuk</label>
                            <input type="time" wire:model="jam_masuk"
                                class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Jam
                                Keluar</label>
                            <input type="time" wire:model="jam_keluar"
                                class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all"
                                required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Berlaku
                            di Cabang (Opsional)</label>
                        <select wire:model="branch_id"
                            class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all">
                            <option value="">Semua Cabang (Global)</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->nama_cabang }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-neutral-400 dark:text-neutral-500 mt-1 italic">Kosongkan jika shift
                            berlaku untuk semua cabang.</p>
                    </div>

                    <hr class="my-4 border-neutral-100 dark:border-neutral-700">

                    <div>
                        <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Denda
                            Keterlambatan (Rp)</label>
                        <input type="number" wire:model="denda_telat"
                            class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all"
                            required>
                        @error('denda_telat') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Denda Lupa Clock Out (Rp)</label>
                        <input type="number" wire:model="denda_missing_clockout"
                            class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all {{ !auth()->user()->hasRole('superadmin') ? 'cursor-not-allowed opacity-60' : '' }}"
                            {{ !auth()->user()->hasRole('superadmin') ? 'disabled' : '' }}
                            required>
                        @error('denda_missing_clockout') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Maks.
                                Telat (Menit)</label>
                            <input type="number" wire:model="maksimal_telat_menit"
                                class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all"
                                required>
                            <p class="text-[10px] text-neutral-400 dark:text-neutral-500 mt-1 italic">Batas toleransi
                                sebelum dianggap Alpha.</p>
                            @error('maksimal_telat_menit') <span
                            class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Batas Awal Absen (Menit)</label>
                            <input type="number" wire:model="batas_awal_absen_menit"
                                class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all"
                                required>
                            <p class="text-[10px] text-neutral-400 dark:text-neutral-500 mt-1 italic">Maksimal menit sebelum shift boleh absen.</p>
                            @error('batas_awal_absen_menit') <span
                            class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex gap-2 pt-4">
                        <button type="submit"
                            class="flex-1 bg-primary hover:bg-neutral-900 dark:hover:bg-neutral-700 text-white font-bold py-2.5 rounded-xl transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                            <i class="ri-save-line text-[18px] leading-none"></i>
                            {{ $isEdit ? 'Update' : 'Simpan' }}
                        </button>
                        @if($isEdit)
                            <button type="button" wire:click="resetFields"
                                class="bg-neutral-100 hover:bg-neutral-200 dark:bg-neutral-700 dark:hover:bg-neutral-600 text-neutral-700 dark:text-neutral-200 font-bold py-2.5 px-4 rounded-xl transition-all">
                                Batal
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Shift -->
        <div class="lg:col-span-8">
            <div
                class="bg-white dark:bg-neutral-800 p-6 rounded-2xl shadow-sm border border-neutral-200 dark:border-neutral-700 h-full">
                <h2 class="text-lg font-bold text-neutral-900 dark:text-neutral-100 mb-6 flex items-center gap-2">
                    <i class="ri-list-check-2 text-primary text-xl leading-none"></i>
                    Daftar Shift Tersedia
                </h2>

                <div class="overflow-x-auto rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-neutral-50 dark:bg-neutral-900/50 text-neutral-500 dark:text-neutral-400 font-medium uppercase tracking-wider text-xs border-b border-neutral-200 dark:border-neutral-700">
                                <th class="p-4">Nama Shift</th>
                                <th class="p-4">Cabang</th>
                                <th class="p-4">Jam Kerja</th>
                                <th class="p-4">Aturan Telat</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-neutral-100 dark:divide-neutral-700 text-neutral-700 dark:text-neutral-300 text-sm">
                            @forelse($shifts as $shift)
                                <tr wire:key="shift-row-{{ $shift->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/50 transition-colors">
                                    <td class="p-4 font-bold text-neutral-900 dark:text-neutral-100">
                                        {{ $shift->nama_shift }}
                                    </td>
                                    <td class="p-4">
                                        @if($shift->branch)
                                            <span
                                                class="inline-flex items-center gap-1 bg-neutral-100 dark:bg-neutral-700/50 px-2 py-0.5 rounded-md text-[11px] font-medium text-neutral-600 dark:text-neutral-300">
                                                <i class="ri-store-2-line text-[12px] leading-none"></i>
                                                {{ $shift->branch->nama_cabang }}
                                            </span>
                                        @else
                                            <span class="text-neutral-400 dark:text-neutral-500 text-xs italic">Global</span>
                                        @endif
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span
                                                class="text-xs font-semibold text-blue-700 dark:text-blue-400">{{ \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') }}
                                                - {{ \Carbon\Carbon::parse($shift->jam_keluar)->format('H:i') }}</span>
                                            <span class="text-[10px] text-neutral-400 dark:text-neutral-500">Durasi: 8 Jam
                                                (Wajib)</span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-xs text-red-600 dark:text-red-400 font-semibold">Denda Telat: Rp
                                                {{ number_format($shift->denda_telat, 0, ',', '.') }}</span>
                                            <span class="text-xs text-amber-600 dark:text-amber-400 font-semibold">Denda Lupa Clock Out: Rp
                                                {{ number_format($shift->denda_missing_clockout, 0, ',', '.') }}</span>
                                            <div class="flex flex-col text-[10px] text-neutral-500 dark:text-neutral-400">
                                                <span>Maks. Telat: {{ $shift->maksimal_telat_menit }} Menit</span>
                                                <span>Batas Absen: {{ $shift->batas_awal_absen_menit ?? 60 }} Menit</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex justify-end gap-1">
                                            <button wire:click="editShift({{ $shift->id }})"
                                                class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/30 rounded-lg transition-colors"
                                                title="Edit">
                                                <i class="ri-pencil-line text-[18px] leading-none"></i>
                                            </button>
                                            <button wire:click="deleteShift({{ $shift->id }})"
                                                wire:confirm="Yakin ingin menghapus shift ini?"
                                                class="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg transition-colors"
                                                title="Hapus">
                                                <i class="ri-delete-bin-line text-[18px] leading-none"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-12 text-center">
                                        <div
                                            class="flex flex-col items-center gap-2 text-neutral-400 dark:text-neutral-500">
                                            <i class="ri-time-line text-4xl leading-none"></i>
                                            <p>Belum ada data shift.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $shifts->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
