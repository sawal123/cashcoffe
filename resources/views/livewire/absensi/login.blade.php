<div class="w-full flex flex-col items-center justify-center px-4 py-12">
    <main class="w-full max-w-[400px] flex flex-col items-center space-y-stack-lg mx-auto">
        <!-- Brand Header Section -->
        <header class="flex flex-col items-center space-y-stack-md text-center">
            <div class="w-20 h-20 bg-surface rounded-2xl flex items-center justify-center p-2 shadow-sm border border-outline-variant/50 overflow-hidden">
                <img src="{{ asset($webSetting->logo ?? 'logo/logow.png') }}" class="w-full h-full object-contain" alt="Portal Logo">
            </div>
            <div class="space-y-base">
                <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-primary tracking-tight font-bold">{{ $webSetting->app_name ?? 'WorkSync' }}</h1>
                <p class="font-body-lg text-body-lg text-secondary">Portal Absensi Karyawan</p>
            </div>
        </header>

        <!-- Login Form Container -->
        <section class="w-full bg-surface-container-lowest border border-outline-variant p-container-margin rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.04)]">
            
            @if (session()->has('error'))
                <div class="mb-4 p-3 bg-error-container text-on-error-container rounded-lg text-sm flex items-start gap-2">
                    <span class="material-symbols-outlined text-error shrink-0">error</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form class="space-y-stack-lg" wire:submit="authenticate">
                <!-- Input Group: Employee ID -->
                <div class="space-y-base">
                    <label class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider font-medium" for="employee-id">ID / Email Pegawai</label>
                    <div class="relative group">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant group-focus-within:text-primary transition-colors">badge</span>
                        <input wire:model="identifier" class="w-full pl-10 pr-4 py-3 bg-surface border border-outline-variant rounded-lg font-body-md text-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all placeholder:text-outline-variant" id="employee-id" placeholder="Masukkan ID atau Email" type="text" required />
                    </div>
                    @error('identifier') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Input Group: Password -->
                <div class="space-y-base">
                    <div class="flex justify-between items-center">
                        <label class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider font-medium" for="password">Kata Sandi</label>
                    </div>
                    <div class="relative group" x-data="{ show: false }">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant group-focus-within:text-primary transition-colors">lock</span>
                        <input wire:model="password" :type="show ? 'text' : 'password'" class="w-full pl-10 pr-12 py-3 bg-surface border border-outline-variant rounded-lg font-body-md text-body-md focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all placeholder:text-outline-variant" id="password" placeholder="••••••••" required />
                        <button @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline-variant hover:text-on-surface-variant transition-colors" type="button">
                            <span class="material-symbols-outlined" x-text="show ? 'visibility_off' : 'visibility'">visibility</span>
                        </button>
                    </div>
                    @error('password') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center gap-2">
                    <input wire:model="remember" type="checkbox" id="remember" class="rounded border-outline-variant text-primary focus:ring-primary">
                    <label for="remember" class="text-sm text-on-surface-variant cursor-pointer">Ingat Saya</label>
                </div>

                <!-- Primary Action -->
                <button class="w-full bg-primary-container hover:bg-primary text-on-primary-container hover:text-on-primary py-3.5 rounded-lg font-button text-button font-semibold transition-all duration-200 active:scale-[0.98] shadow-sm flex items-center justify-center gap-2" type="submit">
                    <span wire:loading.remove wire:target="authenticate">Masuk</span>
                    <span wire:loading wire:target="authenticate" class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-current border-t-transparent"></span>
                </button>
            </form>

            <!-- Divider -->
            <div class="flex items-center my-stack-lg">
                <div class="flex-grow border-t border-outline-variant"></div>
                <span class="px-3 font-label-md text-label-md text-outline-variant uppercase">Atau</span>
                <div class="flex-grow border-t border-outline-variant"></div>
            </div>

            <!-- Biometric Action -->
            <button onclick="alert('Fitur Autentikasi Biometrik sedang dikembangkan.')" class="w-full flex items-center justify-center space-x-base py-3 rounded-lg border border-outline-variant hover:bg-surface-container-low transition-colors font-button text-button text-on-surface-variant active:opacity-80">
                <span class="material-symbols-outlined">fingerprint</span>
                <span>Masuk dengan Biometrik</span>
            </button>
        </section>

        <!-- Footer / Support Link -->
        <footer class="text-center mt-8">
            <p class="font-body-md text-body-md text-secondary">
                Kendala saat masuk? <a class="text-primary font-semibold hover:underline" href="https://wa.me/" target="_blank">Hubungi IT Support</a>
            </p>
        </footer>
    </main>

    <!-- Decorative Elements for the background -->
    <div class="fixed top-0 right-0 p-12 opacity-5 pointer-events-none hidden md:block">
        <span class="material-symbols-outlined text-[300px] text-primary">schedule</span>
    </div>
    <div class="fixed bottom-0 left-0 p-12 opacity-5 pointer-events-none hidden md:block">
        <span class="material-symbols-outlined text-[200px] text-primary">verified_user</span>
    </div>
</div>
