<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">Pengaturan Web Dinamis</h6>
        </div>
        <x-breadcrumb title="Pengaturan Web" />
    </div>

    <x-toast />

    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-2xl text-sm font-medium flex items-center gap-3 shadow-sm">
            <iconify-icon icon="lucide:check-circle" class="text-xl shrink-0"></iconify-icon>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Main Setting Form -->
        <div class="lg:col-span-7 bg-white dark:bg-neutral-800 rounded-3xl p-6 md:p-8 border border-neutral-200 dark:border-neutral-700 shadow-sm space-y-6">
            <form wire:submit.prevent="save" class="space-y-6">
                <!-- App Name Input -->
                <div class="space-y-2">
                    <x-ui.input 
                        wire:model="appName" 
                        label="Nama Aplikasi / Web" 
                        placeholder="Contoh: WorkSync CashCoffee" 
                        required 
                        class="!py-3"
                    />
                    <span class="text-xs text-neutral-500">Nama ini akan ditampilkan pada tab peramban dan judul halaman portal.</span>
                </div>

                <hr class="border-neutral-100 dark:border-neutral-700">

                <!-- Logo Upload -->
                <div class="space-y-3">
                    <label class="block font-bold text-neutral-800 dark:text-neutral-200 text-sm mb-1">Unggah Logo Web Utama</label>
                    <div class="flex items-center gap-4">
                        <!-- Preview Box -->
                        <div class="w-20 h-20 bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-2xl flex items-center justify-center p-2 shrink-0 overflow-hidden shadow-inner">
                            @if ($newLogo)
                                <img src="{{ $newLogo->temporaryUrl() }}" class="w-full h-full object-contain" alt="New Logo Preview">
                            @else
                                <img src="{{ asset($currentLogo) }}" class="w-full h-full object-contain" alt="Current Logo">
                            @endif
                        </div>
                        <!-- File Input -->
                        <div class="flex-grow">
                            <input type="file" wire:model="newLogo" accept="image/*" class="block w-full text-sm text-neutral-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 transition-all cursor-pointer">
                            <div wire:loading wire:target="newLogo" class="text-xs text-primary-600 mt-1 font-medium animate-pulse">Mengunggah pratinjau logo...</div>
                            <span class="text-xs text-neutral-400 block mt-1">Format: PNG, JPG, GIF (Maks. 2MB). Disarankan berlatar transparan.</span>
                        </div>
                    </div>
                    @error('newLogo') <span class="text-xs text-red-500 font-medium">{{ $message }}</span> @enderror
                </div>

                <hr class="border-neutral-100 dark:border-neutral-700">

                <!-- Icon/Favicon Upload -->
                <div class="space-y-3">
                    <label class="block font-bold text-neutral-800 dark:text-neutral-200 text-sm mb-1">Unggah Ikon Web (Favicon)</label>
                    <div class="flex items-center gap-4">
                        <!-- Preview Box -->
                        <div class="w-16 h-16 bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 rounded-2xl flex items-center justify-center p-2 shrink-0 overflow-hidden shadow-inner">
                            @if ($newIcon)
                                <img src="{{ $newIcon->temporaryUrl() }}" class="w-full h-full object-contain" alt="New Icon Preview">
                            @else
                                <img src="{{ asset($currentIcon) }}" class="w-full h-full object-contain" alt="Current Icon">
                            @endif
                        </div>
                        <!-- File Input -->
                        <div class="flex-grow">
                            <input type="file" wire:model="newIcon" accept="image/*" class="block w-full text-sm text-neutral-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 transition-all cursor-pointer">
                            <div wire:loading wire:target="newIcon" class="text-xs text-primary-600 mt-1 font-medium animate-pulse">Mengunggah pratinjau ikon...</div>
                            <span class="text-xs text-neutral-400 block mt-1">Format: PNG, ICO (Rasio 1:1, Maks. 1MB). Digunakan untuk tab peramban.</span>
                        </div>
                    </div>
                    @error('newIcon') <span class="text-xs text-red-500 font-medium">{{ $message }}</span> @enderror
                </div>

                <div class="pt-4 flex items-center gap-3 justify-end">
                    <x-ui.button type="submit" color="primary" class="!px-8 !py-3 font-bold shadow-md hover:shadow-lg transition-all">
                        <iconify-icon icon="lucide:save" class="mr-2"></iconify-icon>
                        Simpan Konfigurasi Dinamis
                    </x-ui.button>
                </div>
            </form>
        </div>

        <!-- Information & Preview Card -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white dark:bg-neutral-800 rounded-3xl p-6 border border-neutral-200 dark:border-neutral-700 shadow-sm space-y-4">
                <h6 class="font-bold text-neutral-800 dark:text-neutral-100 text-base flex items-center gap-2">
                    <iconify-icon icon="lucide:info" class="text-primary-600"></iconify-icon>
                    <span>Distribusi Aset Otomatis</span>
                </h6>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 leading-relaxed">
                    Setiap perubahan yang Anda simpan di sini akan secara otomatis dipancarkan ke seluruh antarmuka yang terintegrasi menggunakan variabel tunggal <code class="bg-neutral-100 dark:bg-neutral-900 px-1 py-0.5 rounded text-primary-600">$webSetting</code>.
                </p>
                <div class="p-3 bg-neutral-50 dark:bg-neutral-900/50 rounded-2xl border border-neutral-100 dark:border-neutral-700 space-y-2.5">
                    <span class="block text-[11px] font-bold text-neutral-400 uppercase tracking-wider">Titik Penerapan Dinamis</span>
                    <ul class="text-xs space-y-1.5 text-neutral-600 dark:text-neutral-300">
                        <li class="flex items-center gap-2">
                            <iconify-icon icon="lucide:check" class="text-emerald-500"></iconify-icon>
                            <span>Header Logo Sidebar Dashboard Admin</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <iconify-icon icon="lucide:check" class="text-emerald-500"></iconify-icon>
                            <span>Halaman Masuk (Login) Utama & Absensi</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <iconify-icon icon="lucide:check" class="text-emerald-500"></iconify-icon>
                            <span>Favicon pada Meta Header Peramban</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
