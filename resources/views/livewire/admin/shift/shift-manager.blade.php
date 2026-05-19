<div class="p-6 min-h-screen">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 border-b border-gray-200 pb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Master Data Shift</h1>
            <p class="text-gray-500 mt-1">Kelola daftar shift kerja, jam operasional, dan aturan denda keterlambatan.</p>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-200 flex items-center gap-3 shadow-sm">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-medium text-sm">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Form Shift -->
        <div class="lg:col-span-4">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 sticky top-24">
                <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">{{ $isEdit ? 'edit' : 'add_circle' }}</span>
                    {{ $isEdit ? 'Edit Shift' : 'Tambah Shift Baru' }}
                </h2>
                
                <form wire:submit.prevent="saveShift" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Shift</label>
                        <input type="text" wire:model="nama_shift" placeholder="Contoh: Pagi, Siung, Full Day" class="w-full bg-gray-50 border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-gray-900 transition-all" required>
                        @error('nama_shift') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Jam Masuk</label>
                            <input type="time" wire:model="jam_masuk" class="w-full bg-gray-50 border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-gray-900 transition-all" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Jam Keluar</label>
                            <input type="time" wire:model="jam_keluar" class="w-full bg-gray-50 border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-gray-900 transition-all" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Berlaku di Cabang (Opsional)</label>
                        <select wire:model="branch_id" class="w-full bg-gray-50 border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-gray-900 transition-all">
                            <option value="">Semua Cabang (Global)</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->nama_cabang }}</option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1 italic">Kosongkan jika shift berlaku untuk semua cabang.</p>
                    </div>

                    <hr class="my-4 border-gray-100">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Denda Keterlambatan (Rp)</label>
                        <input type="number" wire:model="denda_telat" class="w-full bg-gray-50 border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-gray-900 transition-all" required>
                        @error('denda_telat') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Maks. Telat (Menit)</label>
                        <input type="number" wire:model="maksimal_telat_menit" class="w-full bg-gray-50 border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-gray-900 transition-all" required>
                        <p class="text-[10px] text-gray-400 mt-1 italic">Batas toleransi sebelum dianggap Alpha.</p>
                        @error('maksimal_telat_menit') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex gap-2 pt-4">
                        <button type="submit" class="flex-1 bg-primary hover:bg-black text-white font-bold py-2.5 rounded-xl transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">save</span>
                            {{ $isEdit ? 'Update' : 'Simpan' }}
                        </button>
                        @if($isEdit)
                            <button type="button" wire:click="resetFields" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-xl transition-all">
                                Batal
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Shift -->
        <div class="lg:col-span-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200 h-full">
                <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">list</span>
                    Daftar Shift Tersedia
                </h2>

                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 font-medium uppercase tracking-wider text-xs border-b border-gray-200">
                                <th class="p-4">Nama Shift</th>
                                <th class="p-4">Cabang</th>
                                <th class="p-4">Jam Kerja</th>
                                <th class="p-4">Aturan Telat</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-gray-700 text-sm">
                            @forelse($shifts as $shift)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="p-4 font-bold text-gray-900">
                                        {{ $shift->nama_shift }}
                                    </td>
                                    <td class="p-4">
                                        @if($shift->branch)
                                            <span class="inline-flex items-center gap-1 bg-gray-100 px-2 py-0.5 rounded-md text-[11px] font-medium text-gray-600">
                                                <span class="material-symbols-outlined text-[12px]">store</span>
                                                {{ $shift->branch->nama_cabang }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-xs italic">Global</span>
                                        @endif
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-xs font-semibold text-blue-700">{{ \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->jam_keluar)->format('H:i') }}</span>
                                            <span class="text-[10px] text-gray-400">Durasi: 8 Jam (Wajib)</span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-xs text-red-600">Denda: Rp {{ number_format($shift->denda_telat, 0, ',', '.') }}</span>
                                            <span class="text-[10px] text-gray-500">Maks. Telat: {{ $shift->maksimal_telat_menit }} Menit</span>
                                        </div>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex justify-end gap-1">
                                            <button wire:click="editShift({{ $shift->id }})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Edit">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </button>
                                            <button wire:click="deleteShift({{ $shift->id }})" wire:confirm="Yakin ingin menghapus shift ini?" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-12 text-center">
                                        <div class="flex flex-col items-center gap-2 text-gray-400">
                                            <span class="material-symbols-outlined text-4xl">schedule</span>
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
