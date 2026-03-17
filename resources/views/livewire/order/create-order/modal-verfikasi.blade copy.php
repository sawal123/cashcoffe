  <x-mdal name="verify-discount-modal">
        <div class="px-6 py-4">
            <h3 class="font-semibold text-lg text-center mb-4 text-slate-800 dark:text-white">Verifikasi Diskon Private
            </h3>

            <p class="text-sm text-center mb-4 text-slate-500">
                Diskon ini memerlukan otorisasi. Silakan masukkan password Admin.
            </p>

            <div class="mt-4 space-y-4 text-sm">
                <div>
                    <label class="font-semibold text-slate-800 dark:text-slate-200">Password / PIN Admin</label>
                    <div class="mt-1">
                        <input type="password" wire:model="adminPassword"
                            class="w-full rounded-lg border border-slate-300 dark:border-slate-700
                               bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200
                               px-3 py-2 text-sm focus:outline-none focus:ring-2
                               focus:ring-blue-500/40 focus:border-blue-500 transition"
                            placeholder="Masukkan password..."
                            @keydown.enter="window.Livewire.find('{{ $_instance->getId() }}').verifyDiscount()">

                        @error('adminPassword')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Action --}}
            <div class="mt-6 flex justify-end gap-2">
                <button @click="$dispatch('close-modal', { name: 'verify-discount-modal' })" type="button"
                    class="px-4 py-2 text-sm rounded bg-red-600 text-white hover:bg-red-700 transition">
                    Batal
                </button>

                <button wire:click="verifyDiscount" wire:loading.attr="disabled" type="button"
                    class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700 transition">
                    <span wire:loading.remove wire:target="verifyDiscount">Verifikasi</span>
                    <span wire:loading wire:target="verifyDiscount">Mengecek...</span>
                </button>
            </div>
        </div>
    </x-mdal>