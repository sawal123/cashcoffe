<?php

namespace App\Livewire\Absensi;

use App\Models\Absensi;
use App\Models\UserShift;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ClockIn extends Component
{
    public function render()
    {
        $absensiHariIni = Absensi::where('user_id', Auth::id())
            ->whereDate('tanggal', now()->toDateString())
            ->first();

        $userShiftToday = UserShift::with('shift')
            ->where('user_id', Auth::id())
            ->whereDate('tanggal', now()->toDateString())
            ->first();

        return view('livewire.absensi.clock-in', [
            'absensi' => $absensiHariIni,
            'userShift' => $userShiftToday,
        ])->layout('layouts.absensi');
    }
}
