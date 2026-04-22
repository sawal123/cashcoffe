<div>
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

                {{-- ROLE --}}
                <x-ui.select wire:model="role_selected" label="Role Access *" class="!bg-white dark:!bg-neutral-900 border border-neutral-200 dark:border-neutral-700">
                    <option value="">-- Pilih Role --</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                    @endforeach
                </x-ui.select>

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
