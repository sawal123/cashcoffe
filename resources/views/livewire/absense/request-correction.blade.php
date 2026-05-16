<main class="max-w-[1280px] mx-auto px-4 md:px-8 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6 text-center md:text-left">
        <h2 class="font-headline-lg-mobile md:font-headline-lg text-primary font-bold">Perbaikan Kehadiran</h2>
    </div>

    <x-toast />

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {{-- Form Side --}}
        <div class="lg:col-span-4">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 md:p-8 shadow-sm sticky top-24">
                <h4 class="font-title-lg text-on-surface mb-6 font-bold">Buat Pengajuan</h4>
                
                <form wire:submit="submit" class="space-y-5">
                    <div class="space-y-base">
                        <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium">Tanggal Perbaikan</label>
                        <input type="date" wire:model="tanggal" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                        @error('tanggal') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-base">
                            <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium text-[10px]">Jam Masuk Baru</label>
                            <input type="time" wire:model="jam_masuk_baru" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                        </div>
                        <div class="space-y-base">
                            <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium text-[10px]">Jam Keluar Baru</label>
                            <input type="time" wire:model="jam_keluar_baru" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                        </div>
                    </div>
                    
                    <div class="space-y-base">
                        <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium">Alasan Koreksi</label>
                        <textarea wire:model="alasan" rows="3" placeholder="Contoh: Lupa scan karena sistem error..." class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"></textarea>
                        @error('alasan') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="pt-4">
                        <button type="submit" class="w-full bg-primary-container hover:bg-primary text-on-primary-container hover:text-on-primary font-button text-button py-4 px-6 rounded-xl transition-all active:scale-95 flex items-center justify-center gap-2 shadow-sm font-bold">
                            <span class="material-symbols-outlined text-[20px]">edit_calendar</span>
                            Kirim Perbaikan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- History Side --}}
        <div class="lg:col-span-8">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-outline-variant/60">
                    <h4 class="font-title-lg text-on-surface font-bold">Riwayat Koreksi</h4>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-surface-container-low text-on-surface-variant">
                            <tr>
                                <th class="px-6 py-4 font-label-md uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-4 font-label-md uppercase tracking-wider">Jam Baru</th>
                                <th class="px-6 py-4 font-label-md uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 font-label-md uppercase tracking-wider text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/30">
                            @forelse ($corrections as $cor)
                                <tr class="hover:bg-surface-container-low/30 transition-colors">
                                    <td class="px-6 py-5">
                                        <div class="font-body-md font-bold text-on-surface">
                                            {{ \Carbon\Carbon::parse($cor->tanggal)->translatedFormat('d M Y') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-2 font-label-md text-label-md font-semibold">
                                            <span class="text-primary">{{ $cor->jam_masuk_baru ?? '-' }}</span>
                                            <span class="material-symbols-outlined text-[14px] text-outline-variant">arrow_forward</span>
                                            <span class="text-error">{{ $cor->jam_keluar_baru ?? '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        @php
                                            $statusStyle = match($cor->status) {
                                                'approved' => 'bg-emerald-100 text-emerald-700',
                                                'rejected' => 'bg-error-container text-on-error-container',
                                                default => 'bg-secondary-container text-on-secondary-container'
                                            };
                                        @endphp
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $statusStyle }}">
                                            {{ $cor->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <button class="w-10 h-10 rounded-full hover:bg-surface-container transition-all flex items-center justify-center text-secondary">
                                            <span class="material-symbols-outlined text-[20px]">info</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-20 font-body-md text-secondary">Belum ada riwayat koreksi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($corrections->hasPages())
                    <div class="p-6 border-t border-outline-variant/30">
                        {{ $corrections->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</main>
