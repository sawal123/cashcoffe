<main class="max-w-[1280px] mx-auto px-4 md:px-8 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6 text-center md:text-left">
        <h2 class="font-headline-lg-mobile md:font-headline-lg text-primary font-bold">Perbaikan Kehadiran</h2>
        <button wire:click="createCorrection"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-container text-on-primary hover:text-on-primary-container font-bold rounded-xl shadow-sm active:scale-95 transition-all">
            <span class="material-symbols-outlined text-[20px]">add</span>
            <span>Buat Pengajuan</span>
        </button>
    </div>

    <x-toast />

    <div class="w-full">
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
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button" wire:click="viewCorrection({{ $cor->id }})" 
                                                title="Detail"
                                                class="w-8 h-8 rounded-full hover:bg-surface-container transition-all flex items-center justify-center text-secondary">
                                                <span class="material-symbols-outlined text-[18px]">info</span>
                                            </button>
                                            @if($cor->status === 'pending')
                                                <button type="button" wire:click="editCorrection({{ $cor->id }})"
                                                    title="Edit"
                                                    class="w-8 h-8 rounded-full hover:bg-primary/10 text-primary transition-all flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-[18px]">edit</span>
                                                </button>
                                                <button type="button" 
                                                    wire:click="deleteCorrection({{ $cor->id }})"
                                                    wire:confirm="Apakah Anda yakin ingin membatalkan pengajuan ini?"
                                                    title="Batalkan"
                                                    class="w-8 h-8 rounded-full hover:bg-error/10 text-error transition-all flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                                </button>
                                            @endif
                                        </div>
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

    {{-- Modal Form Koreksi (menggunakan $wire.entangle) --}}
    <div
        x-data="{ open: $wire.entangle('showFormModal') }"
        x-show="open"
        x-transition.opacity.duration.200ms
        x-trap.inert.noscroll="open"
        x-on:keydown.esc.window="open = false"
        x-on:click.self="open = false"
        class="fixed inset-0 z-[100] flex items-start justify-center bg-black/40 backdrop-blur-sm p-4 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        style="display: none;"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200 delay-100"
            x-transition:enter-start="opacity-0 scale-75"
            x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-md rounded-2xl border border-neutral-200 bg-white shadow-xl dark:border-neutral-700 dark:bg-neutral-800 my-auto"
        >
            <div class="flex items-center justify-between border-b border-neutral-200 dark:border-neutral-700 px-6 py-4 bg-surface-container-lowest sticky top-0 z-10 rounded-t-2xl">
                <h3 class="text-lg font-bold text-neutral-800 dark:text-neutral-100">{{ $isEditMode ? 'Edit Pengajuan' : 'Buat Pengajuan' }}</h3>
                <button x-on:click="open = false" aria-label="close modal" class="text-gray-500 hover:text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.6" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="p-6">
                <form wire:submit.prevent="submit" class="space-y-5">
                    <div class="space-y-base">
                        <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium">Tanggal Perbaikan</label>
                        <input type="date" wire:model="tanggal" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                        @error('tanggal') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-base">
                            <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium text-[10px]">Jam Masuk Baru</label>
                            <input type="time" wire:model="jam_masuk_baru" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                            @error('jam_masuk_baru') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-base">
                            <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium text-[10px]">Jam Keluar Baru</label>
                            <input type="time" wire:model="jam_keluar_baru" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                            @error('jam_keluar_baru') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="space-y-base">
                        <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium">Alasan Koreksi</label>
                        <textarea wire:model="alasan" rows="3" placeholder="Contoh: Lupa scan karena sistem error..." class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"></textarea>
                        @error('alasan') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="space-y-base">
                        <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium">Bukti Lampiran (Opsional)</label>
                        <input type="file" wire:model="bukti" class="w-full text-xs text-outline-variant file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary hover:file:text-on-primary transition-all" />
                        @error('bukti') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="pt-4 space-y-2">
                        <button type="submit" class="w-full bg-primary-container hover:bg-primary text-on-primary-container hover:text-on-primary font-button text-button py-4 px-6 rounded-xl transition-all active:scale-95 flex items-center justify-center gap-2 shadow-sm font-bold">
                            <span class="material-symbols-outlined text-[20px]">{{ $isEditMode ? 'save' : 'edit_calendar' }}</span>
                            {{ $isEditMode ? 'Simpan Perubahan' : 'Kirim Perbaikan' }}
                        </button>
                        
                        @if($isEditMode)
                            <button type="button" wire:click="cancelEdit" 
                                class="w-full bg-neutral-100 dark:bg-neutral-800 hover:bg-neutral-200 dark:hover:bg-neutral-700 text-neutral-700 dark:text-neutral-300 font-button text-button py-3 px-6 rounded-xl transition-all flex items-center justify-center gap-2 font-bold border border-outline-variant">
                                <span class="material-symbols-outlined text-[20px]">close</span>
                                Batal Edit
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Detail Perbaikan Kehadiran (menggunakan $wire.entangle) --}}
    <div
        x-data="{ open: $wire.entangle('showDetailModal') }"
        x-show="open"
        x-transition.opacity.duration.200ms
        x-trap.inert.noscroll="open"
        x-on:keydown.esc.window="open = false"
        x-on:click.self="open = false"
        class="fixed inset-0 z-[100] flex items-start justify-center bg-black/40 backdrop-blur-sm p-4 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        style="display: none;"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200 delay-100"
            x-transition:enter-start="opacity-0 scale-75"
            x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-md rounded-2xl border border-neutral-200 bg-white shadow-xl dark:border-neutral-700 dark:bg-neutral-800 my-auto"
        >
            <div class="flex items-center justify-end border-neutral-200 p-4 dark:border-neutral-700">
                <button x-on:click="open = false" aria-label="close modal" class="text-gray-500 hover:text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="1.6" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div wire:loading.flex wire:target="viewCorrection" class="p-20 flex-col items-center justify-center">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
                <p class="mt-4 text-sm text-neutral-500 italic">Memuat data...</p>
            </div>

            <div wire:loading.remove wire:target="viewCorrection">
                @if($viewingCorrection)
                    <div class="px-6 py-4 border-b border-neutral-100 dark:border-neutral-700 flex items-center justify-between bg-surface-container-lowest sticky top-0 z-10">
                        <h3 class="text-lg font-bold text-neutral-800 dark:text-neutral-100">Detail Perbaikan Kehadiran</h3>
                    </div>

                    <div class="p-6 space-y-6">
                        <div class="flex items-center justify-between bg-neutral-50 dark:bg-neutral-900/50 p-4 rounded-2xl border border-neutral-100 dark:border-neutral-700">
                            <div class="space-y-1">
                                <span class="text-[10px] font-black uppercase text-neutral-400 tracking-widest">Tanggal Perbaikan</span>
                                <p class="text-sm font-bold text-neutral-700 dark:text-neutral-300">
                                    {{ \Carbon\Carbon::parse($viewingCorrection->tanggal)->translatedFormat('d M Y') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] font-black uppercase text-neutral-400 tracking-widest">Status</span>
                                <div>
                                    @php
                                        $statusStyle = match($viewingCorrection->status) {
                                            'approved' => 'bg-emerald-100 text-emerald-700',
                                            'rejected' => 'bg-rose-100 text-rose-700',
                                            default => 'bg-amber-100 text-amber-700'
                                        };
                                    @endphp
                                    <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $statusStyle }}">
                                        {{ $viewingCorrection->status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-neutral-50 dark:bg-neutral-900/50 rounded-2xl border border-neutral-100 dark:border-neutral-700">
                                <span class="text-[10px] font-black uppercase text-neutral-400 tracking-widest block mb-1">Jam Masuk Baru</span>
                                <p class="text-sm font-bold text-primary">
                                    {{ $viewingCorrection->jam_masuk_baru ?? '-' }}
                                </p>
                            </div>
                            <div class="p-4 bg-neutral-50 dark:bg-neutral-900/50 rounded-2xl border border-neutral-100 dark:border-neutral-700">
                                <span class="text-[10px] font-black uppercase text-neutral-400 tracking-widest block mb-1">Jam Keluar Baru</span>
                                <p class="text-sm font-bold text-error">
                                    {{ $viewingCorrection->jam_keluar_baru ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="p-4 bg-neutral-50 dark:bg-neutral-900/50 rounded-2xl border border-neutral-100 dark:border-neutral-700">
                            <span class="text-[10px] font-black uppercase text-neutral-400 tracking-widest block mb-2">Alasan Koreksi</span>
                            <p class="text-sm text-neutral-600 dark:text-neutral-400 italic">
                                "{{ $viewingCorrection->alasan }}"
                            </p>
                        </div>

                        @if($viewingCorrection->bukti)
                            <div class="p-4 bg-neutral-50 dark:bg-neutral-900/50 rounded-2xl border border-neutral-100 dark:border-neutral-700">
                                <span class="text-[10px] font-black uppercase text-neutral-400 tracking-widest block mb-2">Bukti Lampiran</span>
                                <a href="{{ asset('storage/' . $viewingCorrection->bukti) }}" target="_blank" class="flex items-center gap-3 p-3 bg-white dark:bg-neutral-800 rounded-xl border border-neutral-100 dark:border-neutral-700 hover:border-primary transition-colors group">
                                    <div class="w-10 h-10 rounded-lg bg-primary/5 text-primary flex items-center justify-center">
                                        <span class="material-symbols-outlined">image</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-neutral-700 dark:text-neutral-300 truncate">Lihat Lampiran</p>
                                        <p class="text-[10px] text-neutral-400">Klik untuk membuka gambar</p>
                                    </div>
                                    <span class="material-symbols-outlined text-neutral-300 group-hover:text-primary transition-colors">open_in_new</span>
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="p-6 bg-neutral-50 dark:bg-neutral-900/50 border-t border-neutral-100 dark:border-neutral-700 flex justify-end">
                        <button x-on:click="open = false" class="px-6 py-2 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-600 text-neutral-700 dark:text-neutral-300 rounded-xl text-xs font-bold hover:bg-neutral-50 transition-all shadow-sm">
                            Tutup
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

</main>
