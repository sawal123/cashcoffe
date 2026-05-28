<div class="p-6 min-h-screen">
    <x-toast />
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 border-b border-neutral-200 dark:border-neutral-700 pb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Generasi Gaji Bulanan</h1>
            <p class="text-neutral-500 dark:text-neutral-400 mt-1">Kalkulasi dan simpan rekapan payroll karyawan berdasarkan siklus cut-off tanggal 26 s/d 25 bulan berikutnya.</p>
        </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white dark:bg-neutral-800 p-6 rounded-2xl shadow-sm border border-neutral-200 dark:border-neutral-700 mb-8">
        <h2 class="text-lg font-bold text-neutral-900 dark:text-neutral-100 mb-4 flex items-center gap-2">
            <i class="ri-bar-chart-2-line text-primary text-xl leading-none"></i>
            Panel Generasi Payroll
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
            <div>
                <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Pilih Bulan</label>
                <div class="relative">
                    <select wire:model.live="month" class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all cursor-pointer appearance-none">
                        @foreach($periodOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    <i class="ri-arrow-down-s-line absolute right-3 top-1/2 -translate-y-1/2 text-lg leading-none text-neutral-400 pointer-events-none"></i>
                </div>
            </div>

            <!-- Dropdown Tahun -->
            <div>
                <label class="block text-sm font-semibold text-neutral-700 dark:text-neutral-300 mb-1.5">Pilih Tahun</label>
                <div class="relative">
                    <select wire:model.live="year" class="w-full bg-neutral-50 dark:bg-neutral-900 border border-neutral-300 dark:border-neutral-700 focus:border-primary focus:ring-2 focus:ring-primary/20 rounded-xl px-4 py-2 text-sm text-neutral-900 dark:text-neutral-100 transition-all cursor-pointer appearance-none">
                        @foreach(range(now()->year - 2, now()->year + 1) as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                    <i class="ri-arrow-down-s-line absolute right-3 top-1/2 -translate-y-1/2 text-lg leading-none text-neutral-400 pointer-events-none"></i>
                </div>
            </div>

            <!-- Tombol Hitung Massal -->
            <div>
                <button wire:click="hitungGajiMassal" wire:loading.attr="disabled"
                    class="w-full bg-primary-600 hover:bg-primary-700 text-white font-semibold py-2 px-4 rounded-xl text-sm transition-all flex items-center justify-center gap-2 shadow-sm disabled:opacity-50">
                    <i wire:loading.remove class="ri-settings-3-line text-sm leading-none"></i>
                    <span wire:loading class="animate-spin spinner-border w-4 h-4 border-2 rounded-full border-t-transparent border-white"></span>
                    <span>Hitung Gaji Massal</span>
                </button>
            </div>
        </div>

        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-950/20 text-blue-800 dark:text-blue-300 rounded-xl border border-blue-100 dark:border-blue-800/30 flex items-center gap-3 text-xs md:text-sm">
            <i class="ri-information-line text-base leading-none"></i>
            <span>Siklus Cut-Off terpilih dihitung dari <strong>{{ $startDateFormatted }}</strong> s/d <strong>{{ $endDateFormatted }}</strong>.</span>
        </div>
    </div>

    <!-- Daftar Hasil Gaji -->
    <div class="bg-white dark:bg-neutral-800 rounded-2xl shadow-sm border border-neutral-200 dark:border-neutral-700 overflow-hidden">
        <div class="p-6 border-b border-neutral-200 dark:border-neutral-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">Daftar Payroll Periode Ini</h3>
            <span class="px-3 py-1 rounded-full bg-neutral-100 dark:bg-neutral-700 text-xs font-semibold text-neutral-600 dark:text-neutral-300">
                {{ $payrolls->count() }} Record
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-neutral-50 dark:bg-neutral-900/50 text-neutral-500 dark:text-neutral-400 font-semibold border-b border-neutral-200 dark:border-neutral-700">
                        <th class="py-4 px-6">#</th>
                        <th class="py-4 px-6">Karyawan</th>
                        <th class="py-4 px-6">Jabatan</th>
                        <th class="py-4 px-6 text-right">Gaji Pokok</th>
                        <th class="py-4 px-6 text-right">Double Shift</th>
                        <th class="py-4 px-6 text-right text-red-600 dark:text-red-400">Total Potongan</th>
                        <th class="py-4 px-6 text-right text-emerald-600 dark:text-emerald-400">Gaji Bersih</th>
                        <th class="py-4 px-6 text-center">Detail Potongan</th>
                        <th class="py-4 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                    @forelse($payrolls as $index => $pr)
                        <tr wire:key="payroll-row-{{ $pr->id }}" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/20 transition-all text-neutral-700 dark:text-neutral-300">
                            <td class="py-4 px-6 font-medium">{{ $index + 1 }}</td>
                            <td class="py-4 px-6 font-semibold text-neutral-900 dark:text-neutral-100">
                                {{ $pr->user->name ?? 'User tidak ditemukan' }}
                            </td>
                            <td class="py-4 px-6">{{ $pr->user?->jabatan?->nama_jabatan ?? '-' }}</td>
                            <td class="py-4 px-6 text-right font-medium">Rp {{ number_format($pr->gaji_pokok, 0, ',', '.') }}</td>
                            <td class="py-4 px-6 text-right text-neutral-500">
                                <div>Rp {{ number_format($pr->insentif_double_shift, 0, ',', '.') }}</div>
                                <button type="button"
                                    wire:click="showDoubleShiftDetails({{ $pr->id }})"
                                    class="mt-2 inline-flex items-center justify-center gap-1 rounded-lg border border-neutral-200 dark:border-neutral-700 px-2.5 py-1 text-[11px] font-semibold text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-900 transition-colors">
                                    <i class="ri-calendar-check-line text-sm leading-none"></i>
                                    <span>Cek Tanggal</span>
                                </button>
                            </td>
                            <td class="py-4 px-6 text-right text-red-600 dark:text-red-400 font-medium">
                                <div>Rp {{ number_format($pr->potongan_alpha + $pr->potongan_telat + $pr->potongan_tidak_clock_out, 0, ',', '.') }}</div>
                                <div class="mt-1 text-[11px] leading-tight text-neutral-500 dark:text-neutral-400 font-normal">Alpha + Telat + Lupa Clock Out</div>
                                <button type="button"
                                    wire:click="showDeductionDetails({{ $pr->id }})"
                                    class="mt-2 inline-flex items-center justify-center gap-1 rounded-lg border border-red-200 dark:border-red-900/70 px-2.5 py-1 text-[11px] font-semibold text-red-600 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors">
                                    <i class="ri-file-list-3-line text-sm leading-none"></i>
                                    <span>Lihat Tanggal</span>
                                </button>
                            </td>
                            <td class="py-4 px-6 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                <div>Rp {{ number_format($pr->gaji_bersih, 0, ',', '.') }}</div>
                                <div class="mt-1 text-[11px] leading-tight text-neutral-500 dark:text-neutral-400 font-normal">Pokok + Double Shift - Potongan</div>
                            </td>
                            <td class="py-4 px-6">
                                <div class="text-xs text-neutral-500 flex flex-col items-center gap-0.5">
                                    <span>Alpha: Rp {{ number_format($pr->potongan_alpha, 0, ',', '.') }}</span>
                                    <span>Telat: Rp {{ number_format($pr->potongan_telat, 0, ',', '.') }}</span>
                                    <span>Lupa Clock Out: Rp {{ number_format($pr->potongan_tidak_clock_out, 0, ',', '.') }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($pr->user)
                                    <a href="{{ route('absense.show', $pr->user_id) }}"
                                        wire:navigate
                                        class="inline-flex items-center justify-center gap-1 rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-700 transition-colors">
                                        <i class="ri-time-line text-sm leading-none"></i>
                                        <span>Detail Absen</span>
                                    </a>
                                @else
                                    <span class="inline-flex items-center justify-center rounded-lg bg-neutral-100 dark:bg-neutral-700 px-3 py-2 text-xs font-semibold text-neutral-400">
                                        Tidak tersedia
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-neutral-400 dark:text-neutral-500">
                                <i class="ri-wallet-3-line text-4xl block mb-2 opacity-55 leading-none"></i>
                                <span>Belum ada data payroll untuk periode ini. Klik tombol di atas untuk melakukan kalkulasi.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($showDetailModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" wire:key="payroll-detail-modal">
            <div class="w-full max-w-4xl rounded-2xl bg-white dark:bg-neutral-800 shadow-xl border border-neutral-200 dark:border-neutral-700 overflow-hidden">
                <div class="flex items-center justify-between gap-4 border-b border-neutral-200 dark:border-neutral-700 p-5">
                    <h3 class="text-lg font-bold text-neutral-900 dark:text-neutral-100">{{ $detailTitle }}</h3>
                    <button type="button"
                        wire:click="closeDetailModal"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 dark:hover:bg-neutral-900 transition-colors">
                        <i class="ri-close-line text-xl leading-none"></i>
                    </button>
                </div>

                <div class="max-h-[70vh] overflow-y-auto p-5">
                    @if(empty($detailRows))
                        <div class="py-10 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            <i class="ri-inbox-line mb-2 block text-4xl leading-none opacity-60"></i>
                            Tidak ada data detail untuk periode ini.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-neutral-200 text-xs font-semibold uppercase text-neutral-500 dark:border-neutral-700 dark:text-neutral-400">
                                        <th class="px-3 py-3">Tanggal</th>
                                        <th class="px-3 py-3">Status</th>
                                        <th class="px-3 py-3">Shift</th>
                                        <th class="px-3 py-3 text-right">Nominal</th>
                                        <th class="px-3 py-3">Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                                    @foreach($detailRows as $row)
                                        <tr wire:key="payroll-detail-row-{{ $loop->index }}" class="text-neutral-700 dark:text-neutral-300">
                                            <td class="px-3 py-3 font-medium text-neutral-900 dark:text-neutral-100">{{ $row['tanggal'] }}</td>
                                            <td class="px-3 py-3">{{ $row['status'] }}</td>
                                            <td class="px-3 py-3">{{ $row['shift'] }}</td>
                                            <td class="px-3 py-3 text-right font-semibold">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                                            <td class="px-3 py-3 text-neutral-500 dark:text-neutral-400">{{ $row['keterangan'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
