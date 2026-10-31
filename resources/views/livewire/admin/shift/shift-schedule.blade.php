<div class="min-h-screen max-w-full overflow-x-hidden" x-data="{ bulkActionOpen: false }">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8 border-b border-gray-200 dark:border-white/5 pb-6">
        <div>
            <h1 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Penjadwalan Shift</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1 font-bold text-sm">Kelola jadwal kerja karyawan secara massal via kalender.</p>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 rounded-2xl border border-emerald-200 dark:border-emerald-500/20 flex items-center gap-3 shadow-sm animate-in fade-in slide-in-from-top-4">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-black text-xs uppercase tracking-wider">{{ session('success') }}</span>
        </div>
    @endif

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }
    </style>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Sidebar List Karyawan -->
        <div class="col-span-12 lg:col-span-4 space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-gray-200 dark:border-white/5 p-5 sticky top-4 shadow-sm">
                <div class="mb-6">
                    <x-ui.input 
                        wire:model.live="searchUser" 
                        placeholder="Cari karyawan..." 
                        prefix='<span class="material-symbols-outlined text-slate-400">search</span>'
                    />
                </div>

                <div class="space-y-2 max-h-[calc(100vh-350px)] overflow-y-auto pr-1 custom-scrollbar">
                    @forelse($users as $user)
                        <button 
                            wire:click="selectUser({{ $user->id }})"
                            class="w-full flex items-center gap-4 p-4 rounded-2xl transition-all duration-200 group relative overflow-hidden
                            {{ $selectedUserId == $user->id 
                                ? 'bg-indigo-600 text-white z-10' 
                                : 'bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-900 dark:text-slate-100 border border-slate-100 dark:border-white/5' }}">
                            
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center font-black text-sm uppercase transition-colors
                                {{ $selectedUserId == $user->id ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600' }}">
                                {{ substr($user->name, 0, 2) }}
                            </div>
                            
                            <div class="text-left flex-1">
                                <div class="text-sm font-black leading-tight">{{ $user->name }}</div>
                                <div class="text-[10px] font-bold uppercase tracking-widest mt-1 {{ $selectedUserId == $user->id ? 'text-indigo-100' : 'text-slate-400 dark:text-slate-500' }}">
                                    {{ $user->branch->nama_cabang ?? 'Pusat' }}
                                </div>
                            </div>

                            @if($selectedUserId == $user->id)
                                <div class="absolute right-4 top-1/2 -translate-y-1/2">
                                    <span class="material-symbols-outlined text-white text-xl">check_circle</span>
                                </div>
                            @endif
                        </button>
                    @empty
                        <div class="p-12 text-center text-slate-400">
                            <span class="material-symbols-outlined text-5xl mb-3 opacity-20">person_off</span>
                            <p class="text-xs font-black uppercase tracking-[0.2em]">Karyawan tidak ditemukan</p>
                        </div>
                    @endforelse
                </div>
                
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-white/5">
                    {{ $users->links() }}
                </div>
            </div>
        </div>

        <!-- Calendar Section -->
        <div class="col-span-12 lg:col-span-8 overflow-hidden">
            @if($selectedUserId)
                <div class="bg-white dark:bg-slate-900 rounded-[3rem] border border-slate-200 dark:border-white/5 p-4 md:p-8 animate-in fade-in slide-in-from-right-8 duration-500 shadow-sm">
                    <!-- Calendar Header -->
                    <div class="flex flex-col md:flex-row justify-between items-center gap-6 mb-10">
                        <div class="flex items-center gap-5">
                            <div class="w-16 h-16 rounded-2xl bg-indigo-600 flex items-center justify-center text-white">
                                <span class="material-symbols-outlined text-[32px]">calendar_month</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-900 dark:text-white leading-tight uppercase tracking-tighter">
                                    {{ \App\Models\User::find($selectedUserId)->name }}
                                </h3>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Live Scheduling Mode</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1.5 rounded-[1.5rem] border border-slate-200 dark:border-white/5 shadow-inner">
                            <button wire:click="prevMonth" class="p-3 hover:bg-white dark:hover:bg-slate-700 rounded-2xl transition-all shadow-sm group">
                                <span class="material-symbols-outlined text-slate-500 dark:text-slate-400 group-hover:text-indigo-600">chevron_left</span>
                            </button>
                            <span class="text-xs font-black min-w-[160px] text-center text-slate-800 dark:text-white uppercase tracking-[0.2em]">
                                {{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->translatedFormat('F Y') }}
                            </span>
                            <button wire:click="nextMonth" class="p-3 hover:bg-white dark:hover:bg-slate-700 rounded-2xl transition-all shadow-sm group">
                                <span class="material-symbols-outlined text-slate-500 dark:text-slate-400 group-hover:text-indigo-600">chevron_right</span>
                            </button>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="flex flex-wrap gap-4 mb-10">
                        <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-500/10 px-5 py-2.5 rounded-2xl border border-emerald-100 dark:border-emerald-500/20 shadow-sm">
                            <span class="material-symbols-outlined text-[20px] text-emerald-600 dark:text-emerald-400">done_all</span>
                            <span class="text-[10px] font-black text-emerald-800 dark:text-emerald-400 uppercase tracking-widest leading-none">Sudah Terplot</span>
                        </div>
                        <div class="flex items-center gap-3 bg-indigo-50 dark:bg-indigo-500/10 px-5 py-2.5 rounded-2xl border border-indigo-100 dark:border-indigo-500/20 shadow-sm">
                            <span class="material-symbols-outlined text-[20px] text-indigo-600 dark:text-indigo-400">edit_square</span>
                            <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest leading-none">Sedang Dipilih</span>
                        </div>
                        <div class="flex items-center gap-3 bg-amber-50 dark:bg-amber-500/10 px-5 py-2.5 rounded-2xl border border-amber-100 dark:border-amber-500/20 shadow-sm">
                            <span class="material-symbols-outlined text-[20px] text-amber-600 dark:text-amber-400">event_available</span>
                            <span class="text-[10px] font-black text-amber-800 dark:text-amber-400 uppercase tracking-widest leading-none">Cuti/Izin Approved</span>
                        </div>
                    </div>

                    <!-- Calendar Grid with Mobile Scroll Support -->
                    <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0 custom-scrollbar">
                        <div class="grid grid-cols-7 min-w-[750px] md:min-w-full rounded-[2rem] overflow-hidden border border-slate-200 dark:border-white/5">
                            @php $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']; @endphp
                            @foreach($days as $day)
                                <div class="p-5 text-center text-[10px] font-black bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-white/5 text-slate-500 dark:text-slate-600 uppercase tracking-[0.3em]">
                                    {{ $day }}
                                </div>
                            @endforeach

                            @foreach($calendar as $dateString)
                                @if($dateString)
                                    @php 
                                        $isToday = $dateString == now()->toDateString();
                                        $isSelected = in_array($dateString, $selectedDates);
                                        $hasSchedule = isset($userSchedules[$dateString]);
                                        $onLeave = isset($userLeaves[$dateString]);
                                        $dayNum = \Carbon\Carbon::parse($dateString)->day;
                                    @endphp
                                    <div 
                                        wire:click="toggleDate('{{ $dateString }}')"
                                        class="relative h-40 p-3 border-r border-b border-slate-100 dark:border-white/5 cursor-pointer transition-all duration-300 group
                                        {{ $isSelected ? 'bg-indigo-50 dark:bg-indigo-500/10 z-10' : ($onLeave ? 'bg-amber-50/50 dark:bg-amber-500/10' : ($hasSchedule ? 'bg-emerald-50/30 dark:bg-emerald-500/5' : 'bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/80')) }}
                                        ">
                                        <div class="flex justify-between items-start mb-3">
                                            <span class="text-sm font-black transition-all {{ $isToday ? 'bg-indigo-600 text-white w-9 h-9 flex items-center justify-center rounded-2xl shadow-xl shadow-indigo-200 scale-110' : ($isSelected ? 'text-indigo-600' : 'text-slate-400 dark:text-slate-700 group-hover:text-slate-900 dark:group-hover:text-white') }}">
                                                {{ $dayNum }}
                                            </span>

                                            @if($isSelected)
                                                <div class="bg-indigo-600 text-white rounded-full p-1 shadow-lg animate-bounce ring-4 ring-indigo-600/20">
                                                    <span class="material-symbols-outlined text-[16px] block font-bold">check</span>
                                                </div>
                                            @elseif($onLeave)
                                                <div class="bg-amber-500 text-white rounded-full p-1 shadow-md">
                                                    <span class="material-symbols-outlined text-[14px] block font-bold">event_available</span>
                                                </div>
                                            @elseif($hasSchedule)
                                                <div class="bg-emerald-500 text-white rounded-full p-1 shadow-md">
                                                    <span class="material-symbols-outlined text-[14px] block font-bold">done_all</span>
                                                </div>
                                            @endif
                                        </div>

                                        @if($onLeave)
                                            <div class="mt-2">
                                                <div class="px-2 py-1 bg-amber-100 dark:bg-amber-500/20 border border-amber-200 dark:border-amber-500/30 rounded-lg">
                                                    <span class="text-[9px] font-black text-amber-800 dark:text-amber-400 uppercase tracking-widest block text-center">
                                                        {{ $userLeaves[$dateString]->jenis }} Approved
                                                    </span>
                                                </div>
                                            </div>
                                        @endif

                                        @if($hasSchedule)
                                            <div class="space-y-1.5 overflow-hidden">
                                                @foreach($userSchedules[$dateString] as $sch)
                                                    <div class="relative group/item">
                                                        <div class="p-2 bg-white dark:bg-slate-800 border border-emerald-100 dark:border-emerald-500/20 rounded-xl shadow-sm flex flex-col gap-0.5 transition-all group-hover/item:scale-[1.05] group-hover/item:shadow-md">
                                                            <div class="flex items-center justify-between">
                                                                <span class="text-[9px] font-black text-emerald-800 dark:text-emerald-400 uppercase tracking-tighter truncate">{{ $sch->shift->nama_shift }}</span>
                                                                <button wire:click.stop="deleteSchedule({{ $sch->id }})" class="hidden group-hover/item:flex text-red-500 hover:text-red-700 bg-red-50 dark:bg-red-500/10 rounded-lg p-0.5 transition-all">
                                                                    <span class="material-symbols-outlined text-[12px]">delete</span>
                                                                </button>
                                                            </div>
                                                            <div class="flex items-center gap-1 text-[8px] text-emerald-600 dark:text-emerald-500 font-black">
                                                                <span class="material-symbols-outlined text-[10px]">timer</span>
                                                                {{ \Carbon\Carbon::parse($sch->shift->jam_masuk)->format('H:i') }} - {{ \Carbon\Carbon::parse($sch->shift->jam_keluar)->format('H:i') }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <div class="h-40 bg-slate-50/20 dark:bg-slate-800/10 border-r border-b border-slate-100 dark:border-white/5"></div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <!-- Empty State -->
                <div class="h-[700px] flex flex-col items-center justify-center bg-white dark:bg-slate-900 rounded-[4rem] border-2 border-dashed border-gray-100 dark:border-white/5">
                    <div class="w-32 h-32 bg-gray-50 dark:bg-slate-800 rounded-[2.5rem] flex items-center justify-center mb-8 shadow-inner animate-pulse">
                        <span class="material-symbols-outlined text-6xl text-gray-200 dark:text-gray-700">group_add</span>
                    </div>
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter mb-2">Pilih Karyawan</h3>
                    <p class="text-xs font-black text-gray-400 dark:text-gray-600 uppercase tracking-[0.3em] max-w-xs text-center leading-loose">Silakan pilih salah satu karyawan di panel kiri untuk mulai mengatur jadwal kerja.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Compact Bulk Action Control -->
    @if(count($selectedDates) > 0)
        <div class="fixed right-5 bottom-5 z-[100]">
            <button
                type="button"
                x-show="!bulkActionOpen"
                x-cloak
                @click="bulkActionOpen = true"
                class="relative flex h-14 w-14 items-center justify-center rounded-2xl border border-indigo-300/40 bg-[#4f46e5] text-white shadow-[0_12px_30px_rgba(79,70,229,0.45)] transition hover:bg-[#6366f1] hover:-translate-y-1 active:scale-95"
                title="Buka pengaturan shift"
                aria-label="Buka pengaturan shift"
            >
                <span class="material-symbols-outlined">bolt</span>
                <span class="absolute -right-2 -top-2 flex h-6 min-w-6 items-center justify-center rounded-full border-2 border-slate-900 bg-amber-400 px-1 text-[10px] font-black text-slate-900">
                    {{ count($selectedDates) }}
                </span>
            </button>

            <div
                x-show="bulkActionOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                class="w-[calc(100vw-2.5rem)] max-w-[420px] overflow-hidden rounded-3xl border border-white/10 bg-slate-900/95 p-4 text-white shadow-[0_20px_60px_rgba(0,0,0,0.45)] backdrop-blur-2xl"
                style="display: none;"
            >
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/15 text-indigo-300">
                            <span class="material-symbols-outlined">event_available</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">Tanggal terpilih</p>
                            <p class="text-lg font-black leading-none">{{ count($selectedDates) }} hari</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="bulkActionOpen = false"
                        class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 transition hover:bg-white/10 hover:text-white"
                        title="Tutup"
                        aria-label="Tutup pengaturan shift"
                    >
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <label for="bulk-shift-select" class="mb-2 block px-1 text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">
                    Shift kerja
                </label>
                <div class="relative overflow-hidden rounded-2xl border border-slate-600 bg-slate-800 shadow-inner transition focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-500/30">
                    <select id="bulk-shift-select" wire:model="targetShiftId" class="block h-14 w-full cursor-pointer appearance-none bg-slate-800 px-4 pr-12 text-sm font-black uppercase tracking-wide text-white outline-none transition focus:bg-slate-700 focus:ring-0">
                        <option value="" class="bg-slate-900 text-slate-500 italic">-- Pilih Shift Kerja --</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}" class="bg-slate-900 text-white font-bold">
                                {{ $shift->nama_shift }} ({{ \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->jam_keluar)->format('H:i') }})
                            </option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 material-symbols-outlined text-indigo-300">expand_more</span>
                </div>

                <div class="mt-3 flex gap-2">
                    <button wire:click="saveBulkSchedule" class="flex-1 rounded-2xl bg-indigo-600 px-4 py-3 text-xs font-black uppercase tracking-wider text-white transition hover:bg-indigo-500 active:scale-95">
                        Terapkan
                    </button>
                    <button wire:click="$set('selectedDates', [])" @click="bulkActionOpen = false" class="rounded-2xl border border-white/10 px-4 py-3 text-xs font-black uppercase tracking-wider text-slate-300 transition hover:bg-white/10 hover:text-white" title="Batalkan semua pilihan">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
