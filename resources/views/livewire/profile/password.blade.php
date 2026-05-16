<main class="max-w-[1280px] mx-auto px-4 md:px-8 py-6">
    <div class="flex flex-col items-center justify-center space-y-6 max-w-md mx-auto text-center">
        <header class="space-y-2">
            <h2 class="font-headline-lg-mobile md:font-headline-lg text-primary font-bold">Ubah Kata Sandi</h2>
            <p class="font-body-md text-secondary">Gunakan password yang kuat untuk menjaga keamanan akun Anda.</p>
        </header>

        <x-toast />

        <section class="w-full bg-surface-container-lowest border border-outline-variant p-6 md:p-8 rounded-xl shadow-sm">
            <div class="w-20 h-20 bg-primary-container/10 rounded-2xl flex items-center justify-center mx-auto mb-8 text-primary">
                <span class="material-symbols-outlined text-[40px]">lock_reset</span>
            </div>
            
            <form wire:submit="update" class="space-y-6 text-left">
                <div class="space-y-base">
                    <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium">Password Saat Ini</label>
                    <input type="password" wire:model="current_password" placeholder="••••••••" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                    @error('current_password') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-base">
                    <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium">Password Baru</label>
                    <input type="password" wire:model="new_password" placeholder="Minimal 8 karakter" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                    @error('new_password') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-base">
                    <label class="font-label-md text-on-surface-variant uppercase tracking-wider font-medium">Konfirmasi Password Baru</label>
                    <input type="password" wire:model="new_password_confirmation" placeholder="Ulangi password baru" class="w-full px-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all" />
                </div>
                
                <div class="pt-4">
                    <button type="submit" class="w-full bg-primary-container hover:bg-primary text-on-primary-container hover:text-on-primary py-4 rounded-lg font-button text-button font-bold transition-all duration-200 active:scale-[0.98] shadow-sm flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[20px]">save</span>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </section>
    </div>
</main>
