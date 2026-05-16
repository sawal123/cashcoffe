<main class="max-w-[1280px] mx-auto px-4 md:px-8 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6 text-center md:text-left">
        <h2 class="font-headline-lg-mobile md:font-headline-lg text-primary font-bold">Pengajuan Izin & Cuti</h2>
    </div>

    <x-toast />

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {{-- Form Side --}}
        <div class="lg:col-span-4">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-6 md:p-8 shadow-sm sticky top-24">
                <h4 class="font-title-lg text-on-surface mb-6 font-bold">Buat Pengajuan</h4>
                
                <form wire:submit="submit" class="space-y-5">
                    <div class="space-y-base">
                        <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium">Tanggal Mulai</label>
                        <input type="date" wire:model.live="tanggal_mulai" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                        @error('tanggal_mulai') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-base">
                        <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium">Tanggal Selesai</label>
                        <input type="date" wire:model.live="tanggal_selesai" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                        @error('tanggal_selesai') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    @if($this->total_hari > 0)
                        <div class="p-3 {{ $this->is_quota_exceeded ? 'bg-error/10 border-error/20' : 'bg-primary/5 border-primary/10' }} rounded-lg flex items-center justify-between transition-colors">
                            <span class="text-xs font-bold {{ $this->is_quota_exceeded ? 'text-error' : 'text-primary' }} uppercase">Total Durasi:</span>
                            <span class="text-sm font-black {{ $this->is_quota_exceeded ? 'text-error' : 'text-primary' }}">{{ $this->total_hari }} Hari</span>
                        </div>
                        @if($this->is_quota_exceeded)
                            <p class="text-[10px] text-error font-bold mt-1 animate-pulse">
                                ⚠️ Melebihi sisa hak cuti Anda (Sisa: {{ Auth::user()->hak_cuti }} Hari)
                            </p>
                        @endif
                    @endif
                    
                    <div class="space-y-base">
                        <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium">Jenis Izin</label>
                        <select wire:model.live="jenis" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <option value="izin">Izin</option>
                            <option value="sakit">Sakit</option>
                            <option value="cuti">Cuti</option>
                        </select>
                        @error('jenis') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="space-y-base">
                        <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium">Alasan</label>
                        <textarea wire:model="alasan" rows="3" placeholder="Jelaskan alasan pengajuan Anda..." class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all"></textarea>
                        @error('alasan') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="space-y-base">
                        <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium">Bukti (Opsional)</label>
                        <input type="file" wire:model="bukti" class="w-full text-xs text-outline-variant file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary hover:file:text-on-primary transition-all" />
                        @error('bukti') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-4">
                        <button type="submit" 
                            {{ $this->is_quota_exceeded ? 'disabled' : '' }}
                            class="w-full {{ $this->is_quota_exceeded ? 'bg-neutral-200 text-neutral-400 cursor-not-allowed' : 'bg-primary-container hover:bg-primary text-on-primary-container hover:text-on-primary' }} font-button text-button py-4 px-6 rounded-xl transition-all active:scale-95 flex items-center justify-center gap-2 shadow-sm font-bold">
                            <span class="material-symbols-outlined text-[20px]">send</span>
                            Kirim Pengajuan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- History Side --}}
        <div class="lg:col-span-8">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-outline-variant/60">
                    <h4 class="font-title-lg text-on-surface font-bold">Riwayat Pengajuan</h4>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-surface-container-low text-on-surface-variant">
                            <tr>
                                <th class="px-6 py-4 font-label-md uppercase tracking-wider">Jenis</th>
                                <th class="px-6 py-4 font-label-md uppercase tracking-wider">Tanggal</th>
                                <th class="px-6 py-4 font-label-md uppercase tracking-wider">Alasan</th>
                                <th class="px-6 py-4 font-label-md uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 font-label-md uppercase tracking-wider text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/30">
                            @forelse ($requests as $req)
                                <tr class="hover:bg-surface-container-low/30 transition-colors">
                                    <td class="px-6 py-5">
                                        <span class="inline-flex px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-widest {{ 
                                            $req->jenis == 'cuti' ? 'bg-secondary-container text-primary' : 
                                            ($req->jenis == 'sakit' ? 'bg-error-container text-on-error-container' : 'bg-primary-container text-on-primary-container')
                                        }}">
                                            {{ $req->jenis }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="font-body-md font-semibold text-on-surface">
                                            {{ \Carbon\Carbon::parse($req->tanggal_mulai)->translatedFormat('d M') }} 
                                            @if($req->tanggal_mulai != $req->tanggal_selesai)
                                                - {{ \Carbon\Carbon::parse($req->tanggal_selesai)->translatedFormat('d M Y') }}
                                            @else
                                                {{ \Carbon\Carbon::parse($req->tanggal_selesai)->translatedFormat('Y') }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <p class="text-xs text-on-surface-variant italic line-clamp-1 max-w-[150px]" title="{{ $req->alasan }}">
                                            "{{ $req->alasan }}"
                                        </p>
                                    </td>
                                    <td class="px-6 py-5">
                                        @php
                                            $statusStyle = match($req->status) {
                                                'approved' => 'bg-emerald-100 text-emerald-700',
                                                'rejected' => 'bg-error-container text-on-error-container',
                                                default => 'bg-secondary-container text-on-secondary-container'
                                            };
                                        @endphp
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $statusStyle }}">
                                            {{ $req->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <button type="button" wire:click="viewRequest({{ $req->id }})" 
                                            class="w-10 h-10 rounded-full hover:bg-surface-container transition-all flex items-center justify-center text-secondary">
                                            <span class="material-symbols-outlined text-[20px]">info</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-20 font-body-md text-secondary">Belum ada riwayat pengajuan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($requests->hasPages())
                    <div class="p-6 border-t border-outline-variant/30">
                        {{ $requests->links(data: ['scroll' => false], view: 'vendor.livewire.tailwind') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Detail Pengajuan --}}
    <x-mdal name="view-request-detail">
        <div wire:loading.flex wire:target="viewRequest" class="p-20 flex-col items-center justify-center">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
            <p class="mt-4 text-sm text-neutral-500 italic">Memuat data...</p>
        </div>

        <div wire:loading.remove wire:target="viewRequest">
            @if($viewingRequest)
                <div class="px-6 py-4 border-b border-neutral-100 dark:border-neutral-700 flex items-center justify-between bg-surface-container-lowest sticky top-0 z-10">
                    <h3 class="text-lg font-bold text-neutral-800 dark:text-neutral-100">Detail Pengajuan</h3>
                </div>

                <div class="p-6 space-y-6">
                    <div class="flex items-center justify-between bg-neutral-50 dark:bg-neutral-900/50 p-4 rounded-2xl border border-neutral-100 dark:border-neutral-700">
                        <div class="space-y-1">
                            <span class="text-[10px] font-black uppercase text-neutral-400 tracking-widest">Jenis</span>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-widest {{ 
                                    $viewingRequest->jenis == 'cuti' ? 'bg-secondary-container text-primary' : 
                                    ($viewingRequest->jenis == 'sakit' ? 'bg-error-container text-on-error-container' : 'bg-primary-container text-on-primary-container')
                                }}">
                                    {{ $viewingRequest->jenis }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] font-black uppercase text-neutral-400 tracking-widest">Status</span>
                            <div>
                                @php
                                    $statusStyle = match($viewingRequest->status) {
                                        'approved' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-rose-100 text-rose-700',
                                        default => 'bg-amber-100 text-amber-700'
                                    };
                                @endphp
                                <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest {{ $statusStyle }}">
                                    {{ $viewingRequest->status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-neutral-50 dark:bg-neutral-900/50 rounded-2xl border border-neutral-100 dark:border-neutral-700">
                            <span class="text-[10px] font-black uppercase text-neutral-400 tracking-widest block mb-1">Tanggal Mulai</span>
                            <p class="text-sm font-bold text-neutral-700 dark:text-neutral-300">
                                {{ \Carbon\Carbon::parse($viewingRequest->tanggal_mulai)->translatedFormat('d M Y') }}
                            </p>
                        </div>
                        <div class="p-4 bg-neutral-50 dark:bg-neutral-900/50 rounded-2xl border border-neutral-100 dark:border-neutral-700">
                            <span class="text-[10px] font-black uppercase text-neutral-400 tracking-widest block mb-1">Tanggal Selesai</span>
                            <p class="text-sm font-bold text-neutral-700 dark:text-neutral-300">
                                {{ \Carbon\Carbon::parse($viewingRequest->tanggal_selesai)->translatedFormat('d M Y') }}
                            </p>
                        </div>
                    </div>

                    <div class="p-4 bg-neutral-50 dark:bg-neutral-900/50 rounded-2xl border border-neutral-100 dark:border-neutral-700">
                        <span class="text-[10px] font-black uppercase text-neutral-400 tracking-widest block mb-2">Alasan</span>
                        <p class="text-sm text-neutral-600 dark:text-neutral-400 italic">
                            "{{ $viewingRequest->alasan }}"
                        </p>
                    </div>

                    @if($viewingRequest->bukti)
                        <div class="p-4 bg-neutral-50 dark:bg-neutral-900/50 rounded-2xl border border-neutral-100 dark:border-neutral-700">
                            <span class="text-[10px] font-black uppercase text-neutral-400 tracking-widest block mb-2">Bukti Lampiran</span>
                            <a href="{{ asset('storage/' . $viewingRequest->bukti) }}" target="_blank" class="flex items-center gap-3 p-3 bg-white dark:bg-neutral-800 rounded-xl border border-neutral-100 dark:border-neutral-700 hover:border-primary transition-colors group">
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
                    <button @click="$dispatch('close-modal', { name: 'view-request-detail' })" class="px-6 py-2 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-600 text-neutral-700 dark:text-neutral-300 rounded-xl text-xs font-bold hover:bg-neutral-50 transition-all shadow-sm">
                        Tutup
                    </button>
                </div>
            @endif
        </div>
    </x-mdal>
</main>
