<main class="max-w-[1280px] mx-auto px-4 md:px-8 py-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Profile Header Section (Asymmetric Bento Style) -->
        <section class="lg:col-span-12">
            <div class="bg-surface-container-lowest border border-outline-variant rounded-xl p-8 flex flex-col md:flex-row items-center gap-8 shadow-sm">
                <div class="relative shrink-0">
                    <div class="w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-primary-container p-1 shadow-lg bg-surface-container">
                        <img alt="Employee Profile Photo" class="w-full h-full object-cover rounded-full" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDcta22ITfw_S8puRg0Fsgei8ieNOEZX_pYHU2H_w0lXOjqul-M7OVaNr8-2CCgOZbFIAPg7ukL9qjHekuu9G8SXAGnSQugYl6X9CFhSfNofZCr7NuUyrg__HkiilQfKpw_NeXLiegT__PRzFUCgXlkwNgYgwsYR2AZravpeZpFKUrfo0zaD7l76PxWpB0NQ-Iv0IZ1k6yzuAdGyDtGb-ucCfwXoBkpVJAujjUZrVNBxtRJ38WOiJtOUPjX3VQsQuUtzd_iogeujAw"/>
                    </div>
                    <button onclick="alert('Fitur ganti foto profil sedang dikembangkan.')" class="absolute bottom-1 right-1 bg-primary text-on-primary p-2 rounded-full border-2 border-surface shadow-md hover:scale-105 transition-transform">
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
</main>
