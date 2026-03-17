<div>
    <div class="relative inline-block" wire:poll.3s x-data="{ open: false }">

        {{-- Tombol Ikon Lonceng --}}
        <button @click="open = !open" @click.outside="open = false"
            class="relative flex justify-center items-center p-2 rounded-full  bg-neutral-200 dark:bg-neutral-700 dark:text-white transition"
            type="button">

            <iconify-icon icon="mingcute:notification-line"
                class="text-2xl text-slate-700 dark:text-slate-200"></iconify-icon>

            @if (count($pendingApprovals) > 0)
                <span class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-danger-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-danger-600"></span>
                </span>
            @endif
        </button>

        {{-- Dropdown Body --}}
        <div x-show="open" x-transition.opacity style="display: none;"
            class="absolute right-0 mt-2 z-50 bg-white dark:bg-neutral-800 rounded-xl shadow-xl w-80 sm:w-96 p-0 border border-slate-100 dark:border-neutral-700">

            {{-- Header --}}
            <div class="py-3 px-4 border-b border-slate-100 dark:border-neutral-700 flex items-center justify-between">
                <h6 class="text-base text-neutral-900 dark:text-white font-semibold mb-0">Permintaan Diskon</h6>
                @if (count($pendingApprovals) > 0)
                    <span
                        class="bg-primary-100 text-primary-800 text-xs font-bold px-2.5 py-0.5 rounded-full dark:bg-primary-900/30 dark:text-primary-400">
                        {{ count($pendingApprovals) }} Baru
                    </span>
                @endif
            </div>

            {{-- Area Scroll Utama --}}
            <div class="max-h-[400px] overflow-y-auto scroll-sm">

                {{-- ======================================= --}}
                {{-- BAGIAN 1: PENDING APPROVALS --}}
                {{-- ======================================= --}}
                <div class="p-2 space-y-2">
                    @forelse($pendingApprovals as $req)
                        <div
                            class="p-3 bg-slate-50 dark:bg-neutral-700/50 rounded-lg border border-slate-200 dark:border-neutral-600 shadow-sm">
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div>
                                    <p class="text-sm font-semibold text-neutral-800 dark:text-neutral-200">
                                        Diskon: {{ $req->discount->kode_diskon ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">
                                        Kasir <span
                                            class="font-medium text-neutral-700 dark:text-neutral-300">{{ $req->kasir->name ?? 'Unknown' }}</span>
                                        meminta izin.
                                    </p>
                                </div>
                                <span class="text-[10px] text-neutral-400 whitespace-nowrap">
                                    {{ $req->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <div class="flex gap-2 mt-3">
                                <button wire:click="approveDiscount({{ $req->id }})"
                                    class="flex-1 py-1.5 px-3 bg-primary-600 hover:bg-primary-700 text-white text-xs font-medium rounded-md transition flex justify-center items-center gap-1">
                                    <iconify-icon icon="mingcute:check-line"></iconify-icon> Setujui
                                </button>
                                <button wire:click="rejectDiscount({{ $req->id }})"
                                    class="flex-1 py-1.5 px-3 bg-danger-100 hover:bg-danger-200 text-danger-700 dark:bg-danger-900/30 dark:hover:bg-danger-900/50 dark:text-danger-400 text-xs font-medium rounded-md transition flex justify-center items-center gap-1">
                                    <iconify-icon icon="mingcute:close-line"></iconify-icon> Tolak
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="py-4 flex flex-col items-center justify-center text-center">
                            <iconify-icon icon="mingcute:check-circle-line"
                                class="text-3xl text-neutral-300 dark:text-neutral-600 mb-1"></iconify-icon>
                            <p class="text-xs text-neutral-500 dark:text-neutral-400">Tidak ada permintaan baru.</p>
                        </div>
                    @endforelse
                </div>

                {{-- ======================================= --}}
                {{-- BAGIAN 2: RIWAYAT / HISTORY --}}
                {{-- ======================================= --}}
                @if (count($historyApprovals) > 0)
                    <div
                        class="px-4 py-2 border-t border-slate-100 dark:border-neutral-700 bg-slate-50/50 dark:bg-neutral-800/80 sticky top-0">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Riwayat
                            Terbaru</span>
                    </div>

                    <div class="p-2 space-y-1">
                        @foreach ($historyApprovals as $hist)
                            <div
                                class="p-3 flex justify-between items-center rounded-lg hover:bg-slate-50 dark:hover:bg-neutral-700/50 transition border border-transparent hover:border-slate-100 dark:hover:border-neutral-600">
                                <div>
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ $hist->discount->kode_diskon ?? 'Unknown' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">
                                        Oleh: {{ $hist->kasir->name ?? 'User' }}
                                    </p>
                                </div>

                                <div class="text-right">
                                    @if ($hist->status === 'approved')
                                        <span
                                            class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-md">
                                            <iconify-icon icon="mingcute:check-circle-fill"></iconify-icon> Disetujui
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1 text-[11px] font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 px-2 py-1 rounded-md">
                                            <iconify-icon icon="mingcute:close-circle-fill"></iconify-icon> Ditolak
                                        </span>
                                    @endif
                                    <p class="text-[10px] text-slate-400 mt-1">{{ $hist->updated_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>
