<x-mdal name="verify-discount-modal">
    <div class="px-6 py-5">
        <h3 class="font-bold text-xl text-center mb-4 text-slate-800 dark:text-white">
            Verifikasi Diskon Private
        </h3>

        @if ($isWaitingApproval)
            {{-- ========================================== --}}
            {{-- 1. TAMPILAN SAAT MENUNGGU PERSETUJUAN --}}
            {{-- ========================================== --}}
            <div class="mt-2 p-6 bg-blue-50 dark:bg-blue-900/30 rounded-xl text-center border border-blue-100 dark:border-blue-800/50"
                wire:poll.2s="checkApprovalStatus">
                <iconify-icon icon="mingcute:loading-fill"
                    class="text-4xl text-blue-500 animate-spin mb-3"></iconify-icon>
                <p class="text-base text-blue-700 dark:text-blue-300 font-semibold">
                    Menunggu persetujuan Admin...
                </p>
                <p class="text-xs text-blue-500 mt-1">Sistem mengecek persetujuan secara otomatis.</p>
            </div>

            <div class="mt-6 flex justify-center">
                <button @click="$dispatch('close-modal', { name: 'verify-discount-modal' })" type="button"
                    class="px-6 py-2 text-sm font-medium rounded-lg bg-slate-200 text-slate-700 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 transition">
                    Tutup & Batal
                </button>
            </div>

        @else
            {{-- ========================================== --}}
            {{-- 2. TAMPILAN FORM INPUT PASSWORD (DEFAULT) --}}
            {{-- ========================================== --}}
            <p class="text-sm text-center mb-5 text-slate-500 dark:text-slate-400">
                Diskon ini memerlukan otorisasi. Silakan masukkan PIN Admin atau minta persetujuan jarak jauh.
            </p>

            <div class="mt-4">
                <label class="block font-semibold text-sm text-slate-800 dark:text-slate-200 mb-1">
                    Password / PIN Admin
                </label>
                <input type="password" wire:model="adminPassword"
                    class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                           bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200
                           px-3 py-2.5 text-sm focus:outline-none focus:ring-2
                           focus:ring-blue-500/40 focus:border-blue-500 transition"
                    placeholder="Masukkan password..."
                    @keydown.enter="window.Livewire.find('{{ $_instance->getId() }}').verifyDiscount()">

                @error('adminPassword')
                    <span class="text-red-500 text-xs mt-1 block font-medium">{{ $message }}</span>
                @enderror
            </div>

            {{-- Action Buttons --}}
            <div class="mt-8 flex flex-wrap-reverse sm:flex-nowrap justify-end gap-2">
                <button @click="$dispatch('close-modal', { name: 'verify-discount-modal' })" type="button"
                    class="w-full sm:w-auto px-4 py-2 text-sm font-medium rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 transition">
                    Batal
                </button>

                <button wire:click="requestAdminApproval" wire:loading.attr="disabled" type="button"
                    class="w-full sm:w-auto px-4 py-2 text-sm font-medium rounded-lg bg-yellow-500 text-white hover:bg-yellow-600 transition flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="requestAdminApproval">Kirim Notif</span>
                    <span wire:loading wire:target="requestAdminApproval">Mengirim...</span>
                </button>

                <button wire:click="verifyDiscount" wire:loading.attr="disabled" type="button"
                    class="w-full sm:w-auto px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition flex items-center justify-center gap-2">
                    <span wire:loading.remove wire:target="verifyDiscount">Verifikasi</span>
                    <span wire:loading wire:target="verifyDiscount">Mengecek...</span>
                </button>
            </div>
        @endif
    </div>
</x-mdal>