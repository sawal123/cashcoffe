<div>
   

    <div class="p-2 max-w-3xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('menu.index') }}"
                class="text-sm text-blue-600 hover:underline flex items-center gap-1 mb-3">
                <iconify-icon icon="lucide:arrow-left"></iconify-icon> Kembali ke Daftar Menu
            </a>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <h1 class="text-2xl font-bold text-slate-800">Menu: {{ $menu->nama_menu }}</h1>
                <p class="text-slate-600 mt-1">Pilih grup varian yang tersedia untuk menu ini. Hanya grup yang dipilih yang akan muncul saat pelanggan memesan menu ini.</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden text-slate-900">
            @if($allGroups->isEmpty())
                <div class="p-12 text-center">
                    <iconify-icon icon="lucide:inbox" class="text-5xl text-slate-300 mb-3 flex justify-center"></iconify-icon>
                    <p class="text-slate-400 italic mb-4">Belum ada grup varian yang dibuat.</p>
                    <a href="{{ route('variant-group.index') }}" wire:navigate
                        class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-bold">
                        <iconify-icon icon="lucide:plus" class="inline mr-1"></iconify-icon>Buat Grup Varian
                    </a>
                </div>
            @else
                <div class="p-6 space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach ($allGroups as $group)
                            <label
                                class="flex items-center gap-4 p-4 rounded-xl border border-slate-200 hover:border-blue-300 hover:bg-blue-50 transition cursor-pointer group"
                                :class="{'bg-blue-50 border-blue-300': true}">
                                <input type="checkbox" wire:model="selectedGroups" value="{{ $group->id }}"
                                    class="rounded text-blue-600 w-5 h-5 flex-shrink-0">
                                <div class="flex-1 min-w-0">
                                    <p class="font-bold text-slate-800">{{ $group->nama_group }}</p>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        @if($group->is_required)
                                            <span class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded font-bold">Wajib</span>
                                        @endif
                                        <span class="text-[10px] bg-blue-100 text-blue-600 px-2 py-0.5 rounded font-bold">
                                            {{ ucfirst($group->selection_type) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">
                                        {{ $group->options->count() }} opsi: {{ $group->options->pluck('nama_opsi')->implode(', ') }}
                                    </p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="p-6 bg-slate-50 border-t border-slate-200 flex justify-between items-center">
                    <p class="text-sm text-slate-500">
                        Dipilih: <span class="font-bold">{{ count($selectedGroups) }}</span> / <span class="font-bold">{{ $allGroups->count() }}</span> grup
                    </p>
                    <button wire:click="save" wire:loading.attr="disabled"
                        class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-8 py-2 rounded-lg font-bold transition">
                        <span wire:loading.remove>
                            <iconify-icon icon="lucide:save" class="inline mr-1"></iconify-icon>Simpan Perubahan
                        </span>
                        <span wire:loading>
                            <iconify-icon icon="eos-icons:loading" class="inline mr-1"></iconify-icon>Menyimpan...
                        </span>
                    </button>
                </div>
            @endif
        </div>

        {{-- Info Box --}}
        <div class="mt-6 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-lg p-4">
            <div class="flex gap-3">
                <iconify-icon icon="lucide:lightbulb" class="text-2xl text-amber-600 flex-shrink-0"></iconify-icon>
                <div class="text-sm text-slate-700">
                    <p class="font-bold text-amber-900 mb-1">💡 Tips:</p>
                    <ul class="text-xs space-y-1 text-amber-800">
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