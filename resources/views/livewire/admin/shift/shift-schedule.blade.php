<div class=" min-h-screen max-w-full overflow-x-hidden">
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

    <!-- Floating Bulk Action Bar (Modal-like) -->
    @if(count($selectedDates) > 0)
        <div class="fixed bottom-12 left-1/2 -translate-x-1/2 w-[90%] max-w-[800px] z-[100] animate-in fade-in zoom-in-95 slide-in-from-bottom-12 duration-500">
            <div class="bg-slate-900/95 backdrop-blur-2xl text-white p-3 rounded-[2.5rem] shadow-[0_30px_100px_rgba(0,0,0,0.5)] border border-white/10 overflow-hidden ring-8 ring-black/5">
                <div class="flex flex-col md:flex-row items-center gap-4 p-2">
                    <div class="flex items-center gap-4 bg-white/10 px-6 py-4 rounded-[1.8rem] border border-white/5 shadow-inner">
                        <span class="text-3xl font-black text-indigo-400 tabular-nums">{{ count($selectedDates) }}</span>
                        <div class="h-8 w-px bg-white/10"></div>
                        <span class="text-[10px] uppercase tracking-[0.2em] font-black text-slate-400 leading-tight">Tanggal<br>Terpilih</span>
                    </div>

                    <div class="flex-1 w-full flex items-center gap-3 bg-white/5 p-2 rounded-[1.8rem] border border-white/5 shadow-inner">
                        <div class="flex-1 relative">
                            <select wire:model="targetShiftId" class="w-full bg-transparent border-none focus:ring-0 text-xs font-black py-3.5 pl-6 pr-10 appearance-none bg-none cursor-pointer uppercase tracking-widest text-white">
                                <option value="" class="bg-slate-900 text-slate-500 italic">-- Pilih Shift Kerja --</option>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}" class="bg-slate-900 text-white font-bold">
                                        {{ $shift->nama_shift }} ({{ \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->jam_keluar)->format('H:i') }})
                                    </option>
                                @endforeach
                            </select>
                            <span class="absolute right-5 top-1/2 -translate-y-1/2 material-symbols-outlined text-indigo-400 pointer-events-none">expand_more</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <button wire:click="saveBulkSchedule" class="flex-1 md:flex-none bg-white hover:bg-indigo-600 hover:text-white text-slate-900 font-black px-10 py-5 rounded-[1.8rem] text-xs uppercase tracking-[0.2em] transition-all active:scale-95 shadow-2xl flex items-center justify-center gap-3 group">
                            <span class="material-symbols-outlined text-[20px] group-hover:rotate-12 transition-transform">bolt</span>
                            Terapkan
                        </button>
                        <button wire:click="$set('selectedDates', [])" class="p-5 text-slate-500 hover:text-white hover:bg-white/10 rounded-[1.8rem] transition-all group" title="Batalkan Semua Pilihan">
                            <span class="material-symbols-outlined text-[24px] group-hover:rotate-90 transition-transform">close</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
