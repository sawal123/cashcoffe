<x-mdal name="confirm-cancel-modal">
    <div class="p-8">
        <div class="w-16 h-16 rounded-3xl bg-red-50 dark:bg-red-900/30 flex items-center justify-center text-red-600 mb-6 mx-auto">
            <iconify-icon icon="mingcute:warning-line" class="text-3xl"></iconify-icon>
        </div>
        
        <h3 class="font-black text-2xl text-center mb-2 text-neutral-900 dark:text-white">
            Batalkan Pesanan?
        </h3>
        <p class="text-sm text-center mb-10 text-neutral-500 dark:text-neutral-400 max-w-xs mx-auto">
            Seluruh data belanja akan dihapus, stok bahan akan dikembalikan, dan kuota voucher akan di-reset.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <button @click="$dispatch('close-modal', { name: 'confirm-cancel-modal' })" type="button"
                class="w-full px-6 py-4 text-xs font-black uppercase tracking-widest rounded-2xl bg-neutral-100 text-neutral-500 hover:bg-neutral-200 dark:bg-neutral-700 dark:text-neutral-300 transition-all active:scale-95">
                Tidak, Kembali
            </button>

            <x-ui.button wire:click="batalkanPesanan('{{ $orderId }}')" wire:loading.attr="disabled" color="danger" 
                class="w-full !py-4 shadow-red-500/30">
                <span wire:loading.remove wire:target="batalkanPesanan">Ya, Batalkan</span>
                <span wire:loading wire:target="batalkanPesanan" class="flex items-center gap-2 justify-center">
                    <iconify-icon icon="mingcute:loading-fill" class="animate-spin text-xl"></iconify-icon>
                    <span>Memproses...</span>
                </span>
            </x-ui.button>
        </div>
    </div>
</x-mdal>