<main class="max-w-[1280px] mx-auto px-4 md:px-8 py-6">
    <x-toast />

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Profile Header Section (Asymmetric Bento Style) -->
        <section class="lg:col-span-12">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 flex flex-col md:flex-row items-center gap-8 shadow-sm">
                <div class="relative shrink-0">
                    <div class="w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-primary-container p-1 shadow-lg bg-surface-container">
                        @if ($avatar)
                            <img alt="Pratinjau foto profil" class="w-full h-full object-cover rounded-full" src="{{ $avatar->temporaryUrl() }}">
                        @elseif ($user->avatar)
                            <img alt="Foto profil {{ $user->name }}" class="w-full h-full object-cover rounded-full" src="{{ asset('storage/'.$user->avatar) }}">
                        @else
                            <div class="w-full h-full rounded-full bg-primary-container text-on-primary-container flex items-center justify-center text-4xl font-black">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <button type="button" wire:click="openEditProfile" class="absolute bottom-1 right-1 bg-primary text-on-primary p-2 rounded-full border-2 border-surface shadow-md hover:scale-105 transition-transform" title="Edit profil">
                        <span class="material-symbols-outlined text-[20px]">edit</span>
                    </button>
                </div>
                <div class="text-center md:text-left flex-1">
                    <h2 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary mb-1 font-bold">{{ $user->name }}</h2>
                    <p class="font-body-lg text-body-lg text-secondary mb-2 font-medium capitalize">{{ $user->roles->pluck('name')->join(', ') ?: 'Karyawan' }} • Divisi Operasional</p>
                    <div class="inline-flex items-center gap-2 bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full font-label-md text-label-md font-medium">
                        <span class="material-symbols-outlined text-[16px]">badge</span>
                        <span>ID: EMP-{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="mt-2 text-xs text-secondary font-medium">Email: {{ $user->email }}</div>
                    <button type="button" wire:click="openEditProfile"
                        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-primary-container px-4 py-2 text-sm font-bold text-on-primary-container transition-colors hover:bg-primary hover:text-on-primary">
                        <span class="material-symbols-outlined text-[18px]">manage_accounts</span>
                        Edit Profil
                    </button>
                </div>
                
                <div class="grid grid-cols-3 gap-3 w-full md:w-auto mt-4 md:mt-0">
                    <div class="bg-surface-container text-center p-3 md:p-4 rounded-xl min-w-[100px] border border-outline-variant/60">
                        <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-[10px]">Kehadiran</p>
                        <p class="font-headline-md text-headline-md text-primary font-bold">{{ $persentase }}%</p>
                    </div>
                    <div class="bg-surface-container text-center p-3 md:p-4 rounded-xl min-w-[100px] border border-outline-variant/60">
                        <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-[10px]">Jam Kerja</p>
                        <p class="font-headline-md text-headline-md text-primary font-bold">{{ $totalJamKerja }}h</p>
                    </div>
                    <div class="bg-surface-container text-center p-3 md:p-4 rounded-xl min-w-[100px] border border-outline-variant/60">
                        <p class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider text-[10px]">Sisa Cuti</p>
                        <p class="font-headline-md text-headline-md text-tertiary font-bold">{{ $hakCuti }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Navigation Settings List -->
        <section class="lg:col-span-8 flex flex-col gap-4">
            <h3 class="font-title-lg text-title-lg text-on-surface px-1 font-bold">Layanan Mandiri</h3>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                <!-- Pengajuan Cuti -->
                <a href="{{ route('absensi.leave') }}" wire:navigate class="flex items-center justify-between p-5 hover:bg-surface-container-low transition-colors group cursor-pointer">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-primary-container/10 flex items-center justify-center text-primary shrink-0">
                            <span class="material-symbols-outlined">event_busy</span>
                        </div>
                        <div>
                            <p class="font-body-lg text-body-lg font-semibold text-on-surface">Pengajuan Cuti & Izin</p>
                            <p class="font-body-md text-body-md text-on-surface-variant">Ajukan permohonan istirahat atau izin sakit</p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-outline-variant group-hover:translate-x-1 transition-transform">chevron_right</span>
                </a>
                <div class="h-[1px] bg-outline-variant/30 mx-5"></div>
                <!-- Perbaikan Kehadiran -->
                <a href="{{ route('absensi.correction') }}" wire:navigate class="flex items-center justify-between p-5 hover:bg-surface-container-low transition-colors group cursor-pointer">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-primary-container/10 flex items-center justify-center text-primary shrink-0">
                            <span class="material-symbols-outlined">edit_calendar</span>
                        </div>
                        <div>
                            <p class="font-body-lg text-body-lg font-semibold text-on-surface">Perbaikan Kehadiran</p>
                            <p class="font-body-md text-body-md text-on-surface-variant">Koreksi kesalahan jam masuk atau lupa absen</p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-outline-variant group-hover:translate-x-1 transition-transform">chevron_right</span>
                </a>
            </div>

            <h3 class="font-title-lg text-title-lg text-on-surface px-1 mt-4 font-bold">Pengaturan Akun</h3>
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl overflow-hidden shadow-sm">
                <!-- Ubah Kata Sandi -->
                <a href="{{ route('absensi.password') }}" wire:navigate class="flex items-center justify-between p-5 hover:bg-surface-container-low transition-colors group cursor-pointer">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-primary-container/10 flex items-center justify-center text-primary shrink-0">
                            <span class="material-symbols-outlined">lock_reset</span>
                        </div>
                        <div>
                            <p class="font-body-lg text-body-lg font-semibold text-on-surface">Ubah Kata Sandi</p>
                            <p class="font-body-md text-body-md text-on-surface-variant">Amankan akun dengan memperbarui sandi</p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-outline-variant group-hover:translate-x-1 transition-transform">chevron_right</span>
                </a>
                <div class="h-[1px] bg-outline-variant/30 mx-5"></div>
                <!-- Pusat Bantuan -->
                <a onclick="alert('Mengarahkan ke Portal Panduan Perusahaan...')" class="flex items-center justify-between p-5 hover:bg-surface-container-low transition-colors group cursor-pointer">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-primary-container/10 flex items-center justify-center text-primary shrink-0">
                            <span class="material-symbols-outlined">help_center</span>
                        </div>
                        <div>
                            <p class="font-body-lg text-body-lg font-semibold text-on-surface">Pusat Bantuan</p>
                            <p class="font-body-md text-body-md text-on-surface-variant">Panduan penggunaan dan dukungan teknis</p>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-outline-variant group-hover:translate-x-1 transition-transform">chevron_right</span>
                </a>
            </div>

            <!-- Logout Button -->
            <button type="button" wire:click="logout" class="flex items-center justify-center gap-2 p-4 mt-2 bg-error-container hover:bg-error text-on-error-container hover:text-on-error font-button text-button font-bold rounded-xl shadow-sm active:scale-95 transition-all">
                <span class="material-symbols-outlined">logout</span>
                <span>Keluar dari Akun</span>
            </button>
        </section>

        <!-- Sidebar Info / Visual Accent -->
        <section class="lg:col-span-4 flex flex-col gap-6">
            <div class="bg-primary-container text-on-primary-container p-6 rounded-xl relative overflow-hidden h-full min-h-[240px] flex flex-col justify-end shadow-sm">
                <div class="absolute top-0 right-0 p-8 opacity-20 pointer-events-none">
                    <span class="material-symbols-outlined text-[120px]">workspace_premium</span>
                </div>
                <div class="relative z-10">
                    <h4 class="font-title-lg text-title-lg mb-2 font-bold">Kinerja Bulan Ini</h4>
                    <p class="font-body-md text-body-md opacity-90 mb-4 leading-relaxed">Pertahankan ritme kerja Anda. Anda telah mencapai performa kehadiran yang konsisten minggu ini.</p>
                    <div class="w-full bg-on-primary-container/20 h-2 rounded-full overflow-hidden">
                        <div class="bg-on-primary-container h-full rounded-full transition-all duration-500" style="width: {{ $persentase ?: 10 }}%;"></div>
                    </div>
                    <p class="mt-2 font-label-md text-label-md font-semibold">{{ $persentase }}% Menuju Target Produktivitas Bulanan</p>
                </div>
            </div>
        </section>
    </div>

    <div
        x-data="{ open: $wire.entangle('showEditProfileModal') }"
        x-show="open"
        x-transition.opacity.duration.200ms
        x-trap.inert.noscroll="open"
        x-on:keydown.esc.window="open = false"
        x-on:click.self="open = false"
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        style="display: none;"
    >
        <div class="w-full max-w-md rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-2xl">
            <div class="flex items-center justify-between border-b border-outline-variant/60 px-6 py-4">
                <div>
                    <h3 class="font-title-lg font-bold text-on-surface">Edit Profil</h3>
                    <p class="text-xs text-on-surface-variant">Anda dapat mengganti nama dan foto profil.</p>
                </div>
                <button type="button" x-on:click="open = false" class="rounded-full p-1 text-secondary hover:bg-surface-container">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form wire:submit.prevent="updateProfile" class="space-y-5 p-6">
                <div class="flex justify-center">
                    <div class="h-28 w-28 overflow-hidden rounded-full border-4 border-primary-container bg-surface-container">
                        @if ($avatar)
                            <img src="{{ $avatar->temporaryUrl() }}" alt="Pratinjau foto profil" class="h-full w-full object-cover">
                        @elseif ($user->avatar)
                            <img src="{{ asset('storage/'.$user->avatar) }}" alt="Foto profil" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-primary-container text-3xl font-black text-on-primary-container">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Foto Profil</label>
                    <input type="file" wire:model="avatar" accept="image/*"
                        class="w-full rounded-xl border border-outline-variant bg-surface px-3 py-2 text-sm text-on-surface file:mr-3 file:rounded-lg file:border-0 file:bg-primary-container file:px-3 file:py-2 file:font-bold file:text-on-primary-container">
                    <p class="mt-1 text-[11px] text-secondary">Format gambar, maksimal 2 MB.</p>
                    @error('avatar') <span class="mt-1 block text-xs text-error">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Nama</label>
                    <input type="text" wire:model="name"
                        class="w-full rounded-xl border border-outline-variant bg-surface px-4 py-3 text-on-surface focus:border-primary focus:ring-1 focus:ring-primary">
                    @error('name') <span class="mt-1 block text-xs text-error">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Email</label>
                    <div class="flex items-center justify-between rounded-xl border border-outline-variant bg-surface-container px-4 py-3">
                        <span class="text-sm font-medium text-on-surface-variant">{{ $user->email }}</span>
                        <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-secondary">
                            <span class="material-symbols-outlined text-sm">lock</span>
                            Tidak dapat diubah
                        </span>
                    </div>
                </div>

                <div class="flex gap-3 border-t border-outline-variant/40 pt-5">
                    <button type="button" x-on:click="open = false"
                        class="flex-1 rounded-xl border border-outline-variant px-4 py-3 text-sm font-bold text-on-surface transition-colors hover:bg-surface-container">
                        Batal
                    </button>
                    <button type="submit" wire:loading.attr="disabled"
                        class="flex-1 rounded-xl bg-primary px-4 py-3 text-sm font-bold text-on-primary transition-colors hover:bg-primary-container hover:text-on-primary-container disabled:opacity-50">
                        <span wire:loading.remove wire:target="updateProfile">Simpan Profil</span>
                        <span wire:loading wire:target="updateProfile">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
