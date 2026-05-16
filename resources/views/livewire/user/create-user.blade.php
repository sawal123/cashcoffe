<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl }}" wire:navigate class="w-10 h-10 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 text-neutral-400 hover:text-primary-600 transition-all shadow-sm">
                <iconify-icon icon="lucide:arrow-left" class="text-xl"></iconify-icon>
            </a>
            <h6 class="text-2xl font-bold mb-0 text-neutral-800 dark:text-neutral-100">{{ $title ?? 'User' }}</h6>
        </div>
        <x-breadcrumb :title="$title ?? 'User'" />
    </div>
    <div class="py-6 w-full max-w-5xl mx-auto">
        <x-toast />

        {{-- Header Page --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                {{-- Judul Dinamis --}}
                <h1 class="text-2xl font-bold text-neutral-800 dark:text-neutral-100">
                    {{ $isEdit ? 'Edit Data User' : 'Tambah User Baru' }}
                </h1>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    {{ $isEdit ? 'Perbarui informasi pengguna yang sudah terdaftar.' : 'Silakan isi formulir di bawah ini untuk mendaftarkan pengguna baru.' }}
                </p>
            </div>
        </div>

        {{-- Form Card --}}
        <div class="bg-white dark:bg-neutral-800/50 border border-neutral-200 dark:border-neutral-700 rounded-[2rem] shadow-sm overflow-hidden p-6 sm:p-8">
            <form wire:submit="save" class="flex flex-col gap-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- NAMA --}}
                    <x-ui.input wire:model="name" label="Nama Lengkap *" placeholder="Contoh: John Doe" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />

                    {{-- PHONE --}}
                    <x-ui.input wire:model="phone" label="No. Handphone" placeholder="Contoh: 08123456789" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
                </div>

                {{-- EMAIL --}}
                <x-ui.input type="email" wire:model="email" label="Email Address *" placeholder="user@example.com" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />

                {{-- PASSWORD --}}
                <div>
                    <x-ui.input type="password" wire:model="password" label="Password {!! $isEdit ? '' : '<span class=\'text-red-500\'>*</span>' !!}" placeholder="{{ $isEdit ? 'Kosongkan jika tidak ingin mengganti password' : 'Minimal 6 karakter' }}" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
                    @if ($isEdit)
                        <p class="text-[10px] text-neutral-400 mt-1">*Biarkan kosong jika tidak ingin mengubah password.</p>
                    @endif
                </div>

                {{-- BRANCH --}}
                @if(auth()->user()->hasRole('superadmin'))
                <div>
                    <x-ui.select wire:model="branch_id" label="Penempatan Cabang *" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700">
                        <option value="">-- Pilih Cabang --</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->nama_cabang }} ({{ $branch->kode_cabang }})</option>
                        @endforeach
                    </x-ui.select>
                    <p class="text-[10px] text-neutral-400 mt-1">*Pilih cabang tempat user ini akan bertugas.</p>
                </div>
                @endif

                {{-- JABATAN --}}
                <x-ui.select wire:model="jabatan_id" label="Jabatan Karyawan *" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700">
                    <option value="">-- Pilih Jabatan --</option>
                    @foreach ($jabatans as $jab)
                        <option value="{{ $jab->id }}">{{ $jab->nama_jabatan }}</option>
                    @endforeach
                </x-ui.select>

                {{-- ROLE --}}
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">
                        Role Access (Hak Akses Sistem) *
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach ($roles as $role)
                            <label class="flex items-center gap-2 p-3 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 cursor-pointer hover:border-primary-500 transition-colors">
                                <input type="checkbox" wire:model="role_selected" value="{{ $role->name }}" class="w-4 h-4 text-primary-600 border-neutral-300 rounded focus:ring-primary-500">
                                <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ ucfirst($role->name) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('role_selected') <span class="text-xs text-red-500 font-medium">{{ $message }}</span> @enderror
                    <p class="text-[10px] text-neutral-400 mt-1">*Pilih satu atau lebih hak akses untuk user ini. Karyawan biasa wajib diberi role 'karyawan' agar bisa absen.</p>
                </div>

                {{-- PARAMETER KOMPENSASI GAJI & CUTI (Khusus Akses Manajemen Operasional) --}}
                <div class="mt-2 pt-6 border-t border-neutral-100 dark:border-neutral-700 space-y-4">
                    <div class="flex items-center gap-2 mb-2">
                        <iconify-icon icon="solar:wallet-money-bold" class="text-primary-600 text-lg"></iconify-icon>
                        <h3 class="font-bold text-neutral-800 dark:text-neutral-100 text-sm uppercase tracking-wide">Parameter Kompensasi Gaji & Kehadiran</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- GAJI POKOK --}}
                        <x-ui.input type="number" step="0.01" wire:model="gaji_pokok" label="Gaji Pokok (Rp)" placeholder="Contoh: 4500000" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />

                        {{-- TUNJANGAN HARIAN --}}
                        <x-ui.input type="number" step="0.01" wire:model="tunjangan_harian" label="Tunjangan Kehadiran Harian (Rp)" placeholder="Contoh: 50000" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 mt-4">
                        {{-- HAK CUTI --}}
                        <div class="md:w-1/2">
                            <x-ui.input type="number" wire:model="hak_cuti" label="Hak Cuti Tahunan (Hari)" placeholder="Default: 12" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700" />
                        </div>
                    </div>
                    <p class="text-[10px] text-neutral-400 mt-1">*Parameter ini akan menjadi acuan dasar pada modul kalkulasi otomatis saat pencetakan slip/rekap payroll bulanan.</p>
                </div>

                {{-- Footer Action --}}
                <div class="mt-4 pt-6 border-t border-neutral-100 dark:border-neutral-700 flex justify-end gap-3">
                    <x-ui.button type="submit" color="blue">
                        <span wire:loading.remove>
                            <iconify-icon icon="mingcute:check-circle-line" class="mr-2 text-lg align-middle"></iconify-icon>
                            {{ $isEdit ? 'Update Perubahan' : 'Simpan User' }}
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
