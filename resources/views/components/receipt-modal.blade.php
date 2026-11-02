<div x-data="{
        isOpen: false,
        receiptUrl: '',
        open(url) {
            this.receiptUrl = url;
            this.isOpen = true;
            window.dispatchEvent(new CustomEvent('toggle-blur', { detail: true }));
            document.body.classList.add('overflow-hidden');
        },
        close() {
            this.isOpen = false;
            this.receiptUrl = '';
            window.dispatchEvent(new CustomEvent('toggle-blur', { detail: false }));
            document.body.classList.remove('overflow-hidden');
        }
    }"
    x-on:open-receipt-modal.window="open($event.detail.url || $event.detail)"
    x-on:close-receipt-modal.window="close()"
    x-on:keydown.escape.window="close()"
    x-cloak>

    <template x-teleport="body">
        <div x-show="isOpen"
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[9999999] flex items-center justify-center bg-black/60 backdrop-blur-sm p-3 sm:p-4"
            role="dialog"
            aria-modal="true"
            @click.self="close()">

            <div x-show="isOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative flex flex-col w-full max-w-4xl max-h-[94vh] bg-white dark:bg-neutral-800 rounded-3xl shadow-2xl border border-neutral-200 dark:border-neutral-700 overflow-hidden">

                {{-- Header Modal --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100 dark:border-neutral-700 bg-neutral-50/80 dark:bg-neutral-800/80 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <iconify-icon icon="lucide:printer" class="text-lg"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-neutral-800 dark:text-neutral-100 leading-tight">Cetak Struk</h3>
                            <p class="text-xs text-neutral-400 font-medium">Pratinjau & Kontrol Cetak Thermal</p>
                        </div>
                    </div>

                    <button type="button"
                        @click="close()"
                        class="w-9 h-9 rounded-xl bg-neutral-100 dark:bg-neutral-700 text-neutral-500 hover:text-neutral-800 dark:hover:text-white flex items-center justify-center transition"
                        title="Tutup">
                        <iconify-icon icon="mingcute:close-line" class="text-xl"></iconify-icon>
                    </button>
                </div>

                {{-- Body (Iframe Cetak) --}}
                <div class="flex-1 w-full overflow-hidden bg-neutral-100 dark:bg-neutral-900">
                    <iframe x-show="receiptUrl"
                        :src="receiptUrl"
                        allow="bluetooth"
                        class="w-full h-[75vh] sm:h-[80vh] border-0"
                        title="Struk Pesanan"></iframe>
                </div>
            </div>
        </div>
    </template>
</div>

