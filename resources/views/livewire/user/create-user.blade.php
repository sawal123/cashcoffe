<div>
    <div class="max-w-4xl mx-auto py-6">
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
        <div
            class="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-xl shadow-sm overflow-hidden">

            <form wire:submit="save" class="p-6">
                <div class="grid grid-cols-12 gap-6">

                    {{-- NAMA --}}
                    <div class="md:col-span-6 col-span-12">
                        <label class="form-label block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model="name"
                            class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200 focus:ring-primary-500 focus:border-primary-500 transition"
                            placeholder="Contoh: John Doe">
                        @error('name')
                            <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- PHONE --}}
                    <div class="md:col-span-6 col-span-12">
                        <label class="form-label block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                            No. Handphone
                        </label>
                        <input type="text" wire:model="phone"
                            class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200 focus:ring-primary-500 focus:border-primary-500 transition"
                            placeholder="Contoh: 08123456789">
                        @error('phone')
                            <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- EMAIL --}}
                    <div class="col-span-12">
                        <label class="form-label block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" wire:model="email"
                            class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200 focus:ring-primary-500 focus:border-primary-500 transition"
                            placeholder="user@example.com">
                        @error('email')
                            <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- PASSWORD --}}
                    <div class="md:col-span-6 col-span-12">
                        <label class="form-label block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                            Password
                            @if (!$isEdit)
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <input type="password" wire:model="password"
                            class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200 focus:ring-primary-500 focus:border-primary-500 transition"
                            placeholder="{{ $isEdit ? 'Kosongkan jika tidak ingin mengganti password' : 'Minimal 6 karakter' }}">

                        {{-- Hint Text Khusus Edit --}}
                        @if ($isEdit)
                            <p class="text-[10px] text-neutral-400 mt-1">*Biarkan kosong jika tidak ingin mengubah
                                password.</p>
                        @endif

                        @error('password')
                            <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- ROLE --}}
                    <div class="md:col-span-6 col-span-12">
                        <label class="form-label block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                            Role Access <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="role_selected"
                            class="w-full rounded-lg border border-neutral-300 dark:border-neutral-600 px-3 py-2 bg-white dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200 focus:ring-primary-500 focus:border-primary-500 transition">
                            <option value="">-- Pilih Role --</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                        @error('role_selected')
                            <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                {{-- Footer Action --}}
                <div class="mt-8 pt-6 border-t border-neutral-100 dark:border-neutral-700 flex justify-end gap-3">


                    <button type="submit"
                        class="px-5 py-2.5 rounded-lg bg-primary-600 text-white hover:bg-primary-700 text-sm font-medium transition inline-flex items-center shadow-lg shadow-primary-600/20">
                        <span wire:loading.remove>
                            <iconify-icon icon="mingcute:check-circle-line" class="mr-2 text-lg"></iconify-icon>
                            {{ $isEdit ? 'Update Perubahan' : 'Simpan User' }}
                        </span>

                        {{-- Loading Spinner --}}
                        <span wire:loading class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Memproses...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
