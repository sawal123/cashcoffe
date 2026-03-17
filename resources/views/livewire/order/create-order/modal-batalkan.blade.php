<x-mdal name="confirm-cancel-modal">
    <div class="px-6 py-4 mb-4">
        <div class="text-center mb-6">
            <iconify-icon icon="mingcute:warning-fill" class="text-5xl text-red-500 mb-2"></iconify-icon>
            <h3 class="font-semibold text-lg text-slate-800 dark:text-white">Konfirmasi Pembatalan</h3>
            <p class="text-sm mt-2 text-slate-600 dark:text-slate-400">
                Apakah Anda yakin ingin membatalkan pesanan ini?<br>
                Stok bahan yang sudah terpotong akan dikembalikan ke gudang dan kuota voucher akan di-reset.
            </p>
        </div>

        <div class="flex justify-center gap-3 mt-6">
            <button @click="$dispatch('close-modal', { name: 'confirm-cancel-modal' })" type="button"
                class="px-4 py-2 text-sm rounded-lg bg-slate-200 text-slate-800 hover:bg-slate-300 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600 transition">
                Tidak, Kembali
            </button>

            <button wire:click="batalkanPesanan('{{ $orderId }}')" wire:loading.attr="disabled" type="button"
                class="px-4 py-2 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700 transition flex items-center gap-2">
                <span wire:loading.remove wire:target="batalkanPesanan">Ya, Batalkan</span>
                <span wire:loading wire:target="batalkanPesanan">Memproses...</span>
            </button>
        </div>
    </div>
</x-mdal>