<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" wire:navigate class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-400 hover:text-primary-600 transition-all shadow-sm">
                <iconify-icon icon="lucide:arrow-left" class="text-xl"></iconify-icon>
            </a>
            <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Varian' }}</h6>
        </div>
        <x-breadcrumb :title="$title ?? 'Varian'" />
    </div>
   

    <div class="p-2 max-w-3xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('menu.index') }}"
                class="text-sm text-blue-600 hover:underline flex items-center gap-1 mb-3">
                <iconify-icon icon="lucide:arrow-left"></iconify-icon> Kembali ke Daftar Menu
            </a>
            <div class="bg-white dark:bg-neutral-800 border border-neutral-100 dark:border-neutral-700 rounded-3xl p-6 mb-6 shadow-sm">
                <h1 class="text-2xl font-black text-neutral-800 dark:text-neutral-100">Menu: {{ $menu->nama_menu }}</h1>
                <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-2">Pilih grup varian yang tersedia untuk menu ini. Hanya grup yang dipilih yang akan muncul saat pelanggan memesan menu ini.</p>
            </div>
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-3xl shadow-sm border border-neutral-100 dark:border-neutral-700 overflow-hidden">
            @if($allGroups->isEmpty())
                <div class="p-12 text-center text-neutral-500">
                    <iconify-icon icon="lucide:inbox" class="text-5xl text-neutral-300 dark:text-neutral-600 mb-3 flex justify-center"></iconify-icon>
                    <p class="italic mb-4">Belum ada grup varian yang dibuat.</p>
                    <a href="{{ route('variant-group.index') }}" wire:navigate
                        class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-2xl font-bold transition">
                        <iconify-icon icon="lucide:plus" class="inline mr-1"></iconify-icon>Buat Grup Varian
                    </a>
                </div>
            @else
                <div class="p-6 space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($allGroups as $group)
                            @php
                                $isSelected = in_array($group->id, $selectedGroups);
                            @endphp
                            <label
                                class="flex items-center gap-4 p-5 rounded-2xl border transition cursor-pointer group 
                                {{ $isSelected 
                                    ? 'bg-blue-50/50 dark:bg-blue-950/20 border-blue-500 dark:border-blue-600' 
                                    : 'bg-neutral-50/50 dark:bg-neutral-900/30 border-neutral-100 dark:border-neutral-700 hover:border-blue-500 dark:hover:border-blue-600' }}"
                            >
                                <input type="checkbox" wire:model.live="selectedGroups" value="{{ $group->id }}"
                                    class="rounded-lg text-blue-600 w-5 h-5 flex-shrink-0 dark:bg-neutral-950 dark:border-neutral-800 focus:ring-blue-500">
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-neutral-800 dark:text-neutral-200">{{ $group->nama_group }}</p>
                                    <div class="flex flex-wrap gap-2 mt-1.5">
                                        @if($group->is_required)
                                            <span class="text-[10px] bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 px-2 py-0.5 rounded-lg font-bold border border-red-200 dark:border-red-900/50">Wajib</span>
                                        @endif
                                        <span class="text-[10px] bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded-lg font-bold border border-blue-200 dark:border-blue-900/50">
                                            {{ ucfirst($group->selection_type) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-2 line-clamp-1">
                                        {{ $group->options->count() }} opsi: {{ $group->options->pluck('nama_opsi')->implode(', ') }}
                                    </p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="p-6 bg-neutral-50/30 dark:bg-neutral-900/10 border-t border-neutral-100 dark:border-neutral-700 flex justify-between items-center">
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">
                        Dipilih: <span class="font-bold text-neutral-800 dark:text-neutral-200">{{ count($selectedGroups) }}</span> / <span class="font-bold text-neutral-800 dark:text-neutral-200">{{ $allGroups->count() }}</span> grup
                    </p>
                    <button wire:click="save" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-8 py-3 rounded-2xl font-bold transition shadow-lg shadow-blue-500/20">
                        <span wire:loading.remove class="flex items-center gap-1.5">
                            <iconify-icon icon="lucide:save" class="text-lg"></iconify-icon>Simpan Perubahan
                        </span>
                        <span wire:loading class="flex items-center gap-1.5">
                            <iconify-icon icon="eos-icons:loading" class="text-lg"></iconify-icon>Menyimpan...
                        </span>
                    </button>
                </div>
            @endif
        </div>

        {{-- Info Box --}}
        <div class="mt-6 bg-white dark:bg-neutral-800 border border-neutral-100 dark:border-neutral-700 rounded-3xl p-6 shadow-sm">
            <div class="flex gap-3">
                <iconify-icon icon="lucide:lightbulb" class="text-2xl text-amber-500 flex-shrink-0"></iconify-icon>
                <div class="text-sm">
                    <p class="font-bold text-neutral-800 dark:text-neutral-200 mb-2">💡 Tips:</p>
                    <ul class="text-xs space-y-1.5 text-neutral-500 dark:text-neutral-400">
                        <li>✓ Pilih grup varian yang sesuai dengan menu ini</li>
                        <li>✓ Menu berbeda bisa memiliki varian yang berbeda</li>
                        <li>✓ Saat pelanggan memesan, hanya varian pilihan Anda yang tampil</li>
                        <li>✓ Ubah pilihan kapan saja dari halaman ini</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>