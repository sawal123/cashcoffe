<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">Kelola Pengajuan Kehadiran</h6>
        <x-breadcrumb title="Persetujuan" />
    </div>

    <x-toast />

    {{-- Tabs --}}
    <div class="flex items-center gap-2 mb-8 bg-neutral-100 dark:bg-neutral-800 p-1.5 rounded-2xl w-fit">
        <button wire:click="setType('leave')" 
            class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all {{ $type == 'leave' ? 'bg-white dark:bg-neutral-700 shadow-sm text-blue-600 dark:text-blue-400' : 'text-neutral-500 hover:text-neutral-700' }}">
            Izin & Cuti
        </button>
        <button wire:click="setType('correction')" 
            class="px-6 py-2.5 rounded-xl text-sm font-bold transition-all {{ $type == 'correction' ? 'bg-white dark:bg-neutral-700 shadow-sm text-blue-600 dark:text-blue-400' : 'text-neutral-500 hover:text-neutral-700' }}">
            Perbaikan Kehadiran
        </button>
    </div>

    <div class="bg-white dark:bg-neutral-800 rounded-3xl overflow-hidden shadow-sm border border-neutral-100 dark:border-neutral-700">
        @if($type == 'leave')
            <x-ui.table :headers="['Karyawan', 'Jenis', 'Tanggal', 'Alasan', 'Status', ['name' => 'Aksi', 'align' => 'center']]">
                @forelse ($leaves as $req)
                    <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/30 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-[10px] font-bold text-blue-600">
                                    {{ substr($req->user->name, 0, 2) }}
                                </div>
                                <span class="text-sm font-bold text-neutral-700 dark:text-neutral-300">{{ $req->user->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest {{ 
                                $req->jenis == 'cuti' ? 'bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400' : 
                                ($req->jenis == 'sakit' ? 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400' : 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400')
                            }}">
                                {{ $req->jenis }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-bold text-neutral-600 dark:text-neutral-400">
                                {{ \Carbon\Carbon::parse($req->tanggal_mulai)->format('d/m') }} - {{ \Carbon\Carbon::parse($req->tanggal_selesai)->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-[11px] text-neutral-500 line-clamp-1 italic" title="{{ $req->alasan }}">{{ $req->alasan }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $statusColor = match($req->status) {
                                    'approved' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
                                    'rejected' => 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400',
                                    default => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'
                                };
                            @endphp
                            <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $statusColor }}">
                                {{ $req->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($req->status == 'pending')
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="approveLeave({{ $req->id }})" class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all shadow-sm shadow-emerald-600/10" title="Setujui">
                                        <iconify-icon icon="lucide:check" class="text-sm"></iconify-icon>
                                    </button>
                                    <button wire:click="rejectLeave({{ $req->id }})" class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-sm shadow-rose-600/10" title="Tolak">
                                        <iconify-icon icon="lucide:x" class="text-sm"></iconify-icon>
                                    </button>
                                </div>
                            @elseif($req->status == 'approved')
                                <button type="button" 
                                    @click="$dispatch('open-modal', { name: 'confirm-cancel-leave', id: {{ $req->id }} })"
                                    class="px-3 py-1.5 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white transition-all text-[10px] font-bold shadow-sm border border-amber-100 flex items-center gap-1 mx-auto">
                                    <iconify-icon icon="lucide:undo-2"></iconify-icon>
                                    Cancel
                                </button>
                            @else
                                <span class="text-[10px] text-neutral-400 italic">Diproses</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-20 text-neutral-400">Tidak ada pengajuan izin tertunda.</td>
                    </tr>
                @endforelse
            </x-ui.table>
            <div class="p-6">{{ $leaves->links() }}</div>
        @else
            <x-ui.table :headers="['Karyawan', 'Tanggal', 'Jam Baru', 'Alasan', 'Status', ['name' => 'Aksi', 'align' => 'center']]">
                @forelse ($corrections as $cor)
                    <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-900/30 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-[10px] font-bold text-amber-600">
                                    {{ substr($cor->user->name, 0, 2) }}
                                </div>
                                <span class="text-sm font-bold text-neutral-700 dark:text-neutral-300">{{ $cor->user->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-xs font-bold text-neutral-600 dark:text-neutral-400">
                            {{ \Carbon\Carbon::parse($cor->tanggal)->translatedFormat('d M Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2 text-[11px] font-black">
                                <span class="text-emerald-600">{{ $cor->jam_masuk_baru ?? '--' }}</span>
                                <iconify-icon icon="lucide:arrow-right" class="text-neutral-300"></iconify-icon>
                                <span class="text-rose-600">{{ $cor->jam_keluar_baru ?? '--' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-[11px] text-neutral-500 line-clamp-1 italic">{{ $cor->alasan }}</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $statusColor = match($cor->status) {
                                    'approved' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
                                    'rejected' => 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400',
                                    default => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400'
                                };
                            @endphp
                            <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $statusColor }}">
                                {{ $cor->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($cor->status == 'pending')
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="approveCorrection({{ $cor->id }})" class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all shadow-sm shadow-emerald-600/10">
                                        <iconify-icon icon="lucide:check" class="text-sm"></iconify-icon>
                                    </button>
                                    <button wire:click="rejectCorrection({{ $cor->id }})" class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition-all shadow-sm shadow-rose-600/10">
                                        <iconify-icon icon="lucide:x" class="text-sm"></iconify-icon>
                                    </button>
                                </div>
                            @else
                                <span class="text-[10px] text-neutral-400 italic">Diproses</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-20 text-neutral-400">Tidak ada pengajuan perbaikan tertunda.</td>
                    </tr>
                @endforelse
            </x-ui.table>
            <div class="p-6">{{ $corrections->links() }}</div>
        @endif
    </div>

    {{-- Modal Konfirmasi Batal --}}
    <x-mdal name="confirm-cancel-leave">
        <div class="px-6 py-6 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-3xl bg-amber-100 text-amber-600 shadow-sm border border-amber-200">
                <iconify-icon icon="lucide:alert-circle" class="text-2xl"></iconify-icon>
            </div>

            <h3 class="mb-1 text-lg font-bold text-neutral-900 dark:text-neutral-100">Batalkan Persetujuan?</h3>
            <p class="mb-6 text-sm text-neutral-500 dark:text-neutral-400">
                Saldo hak cuti karyawan akan dikembalikan dan data absensi terkait akan dihapus. Pengajuan akan kembali berstatus pending.
            </p>

            <div class="flex justify-center gap-3 border-t pt-6 border-neutral-100 dark:border-neutral-700">
                <button type="button" x-on:click="$dispatch('close-modal', { name: 'confirm-cancel-leave' })"
                    class="px-5 py-2.5 rounded-2xl border border-neutral-300 bg-white text-neutral-700 hover:bg-neutral-50 text-sm font-bold transition">
                    Tutup
                </button>

                <x-ui.button type="button" color="blue"
                    @click="$wire.cancelLeave(selectedId); $dispatch('close-modal', { name: 'confirm-cancel-leave' })"
                    class="!px-5 !py-2.5">
                    Ya, Batalkan
                </x-ui.button>
            </div>
        </div>
    </x-mdal>
</div>
