<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" wire:navigate class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-400 hover:text-primary-600 transition-all shadow-sm">
                <iconify-icon icon="lucide:arrow-left" class="text-xl"></iconify-icon>
            </a>
            <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'Member' }}</h6>
        </div>
        <x-breadcrumb :title="$title ?? 'Member'" />
    </div>
    <div class="py-6 w-full max-w-5xl mx-auto">
        <x-toast />

        {{-- Header Page --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-neutral-800 dark:text-neutral-100">
                    {{ $memberId ? 'Edit Data Member' : 'Tambah Member Baru' }}
                </h1>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    {{ $memberId ? 'Perbarui informasi keanggotaan pelanggan.' : 'Silakan isi formulir di bawah ini untuk mendaftarkan member baru.' }}
                </p>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="bg-white dark:bg-neutral-800/50 border border-neutral-200 dark:border-neutral-700 rounded-[2rem] shadow-sm overflow-hidden p-6 sm:p-8">
            <form wire:submit.prevent="{{ $memberId ? 'update(\'' . $memberId . '\')' : 'simpan' }}" class="flex flex-col gap-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Nama Member --}}
                    <x-ui.input wire:model="name" label="Nama Member *" placeholder="Contoh: Budi Santoso" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />

                    {{-- Email --}}
                    <x-ui.input type="email" wire:model="email" label="Email Address" placeholder="email@example.com" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Nomor Telepon --}}
                    <x-ui.input wire:model="phone" label="Nomor Telepon *" placeholder="0812xxxxxxxx" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />

                    {{-- Alamat --}}
                    <x-ui.input wire:model="address" label="Alamat" placeholder="Masukkan alamat lengkap" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
                </div>

                {{-- Footer Action --}}
                <div class="mt-4 pt-6 border-t border-neutral-100 dark:border-neutral-700 flex justify-end gap-3">
                    <x-ui.button type="submit" color="blue">
                        <span wire:loading.remove>
                            <iconify-icon icon="mingcute:check-circle-line" class="mr-2 text-lg align-middle"></iconify-icon>
                            {{ $memberId ? 'Update Member' : 'Simpan Member' }}
                        </span>

                        {{-- Loading Spinner --}}
                        <span wire:loading class="flex items-center">
                            <iconify-icon icon="mingcute:loading-fill" class="animate-spin mr-2 text-lg align-middle"></iconify-icon>
                            Memproses...
                        </span>
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
