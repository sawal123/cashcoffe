<x-mdal name="verify-discount-modal">
    <div class="p-8">
        <div class="w-16 h-16 rounded-3xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 mb-6 mx-auto">
            <iconify-icon icon="mingcute:shield-check-line" class="text-3xl"></iconify-icon>
        </div>
        
        <h3 class="font-black text-2xl text-center mb-2 text-neutral-900 dark:text-white">
            Otorisasi Diskon
        </h3>
        <p class="text-sm text-center mb-8 text-neutral-500 dark:text-neutral-400">
            Diskon ini memerlukan persetujuan Admin. Silakan masukkan PIN atau minta persetujuan jarak jauh.
        </p>

        @if ($isWaitingApproval)
            <div class="p-8 bg-blue-50 dark:bg-blue-900/20 rounded-[2rem] text-center border border-blue-100 dark:border-blue-800/50"
                wire:poll.2s="checkApprovalStatus">
                <div class="relative w-12 h-12 mx-auto mb-4">
                    <div class="absolute inset-0 border-4 border-blue-100 dark:border-blue-800 rounded-full"></div>
                    <div class="absolute inset-0 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <p class="text-sm text-blue-700 dark:text-blue-300 font-black uppercase tracking-widest">
                    Menunggu Admin...
                </p>
                <p class="text-[10px] text-blue-500 mt-2 font-medium">Sistem akan otomatis menutup modal jika disetujui.</p>
            </div>

            <div class="mt-8 flex justify-center">
                <button @click="$dispatch('close-modal', { name: 'verify-discount-modal' })" type="button"
                    class="px-8 py-3 text-xs font-black uppercase tracking-widest rounded-2xl bg-neutral-100 text-neutral-500 hover:bg-neutral-200 dark:bg-neutral-700 dark:text-neutral-300 transition-all active:scale-95">
                    Batalkan Permintaan
                </button>
            </div>

        @else
            <div class="space-y-6">
                <x-ui.input type="password" wire:model="adminPassword" label="PIN / Password Admin"
                    placeholder="••••••••"
                    @keydown.enter="window.Livewire.find('{{ $_instance->getId() }}').verifyDiscount()" />

                @error('adminPassword')
                    <div class="flex items-center gap-2 text-red-500 animate-shake">
                        <iconify-icon icon="mingcute:warning-line"></iconify-icon>
                        <span class="text-xs font-bold">{{ $message }}</span>
                    </div>
                @enderror
            </div>

            <div class="mt-10 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button wire:click="requestAdminApproval" wire:loading.attr="disabled" type="button"
                    class="w-full px-6 py-4 text-xs font-black uppercase tracking-widest rounded-2xl bg-neutral-50 text-neutral-600 hover:bg-neutral-100 dark:bg-neutral-900 dark:text-neutral-400 dark:hover:bg-neutral-800 border border-neutral-100 dark:border-neutral-700 transition-all flex items-center justify-center gap-2">
                    <iconify-icon icon="mingcute:notification-line" class="text-lg" wire:loading.remove wire:target="requestAdminApproval"></iconify-icon>
                    <span wire:loading.remove wire:target="requestAdminApproval">Kirim Notifikasi</span>
                    <span wire:loading wire:target="requestAdminApproval" class="flex items-center gap-2">
                        <iconify-icon icon="mingcute:loading-fill" class="animate-spin text-lg"></iconify-icon>
                        <span>Mengirim...</span>
                    </span>
                </button>

                <x-ui.button wire:click="verifyDiscount" wire:loading.attr="disabled" type="button" 
                    class="w-full !py-4 shadow-blue-500/30">
                    <span wire:loading.remove wire:target="verifyDiscount">Verifikasi</span>
                    <span wire:loading wire:target="verifyDiscount" class="flex items-center gap-2 justify-center">
                        <iconify-icon icon="mingcute:loading-fill" class="animate-spin text-xl"></iconify-icon>
                        <span>Mengecek...</span>
                    </span>
                </x-ui.button>
            </div>
            
            <div class="mt-6 flex justify-center">
                <button @click="$dispatch('close-modal', { name: 'verify-discount-modal' })" type="button"
                    class="text-[10px] font-black uppercase tracking-widest text-neutral-400 hover:text-neutral-600 transition-colors">
                    Kembali ke Kasir
                </button>
            </div>
        @endif
    </div>
</x-mdal>