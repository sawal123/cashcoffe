<?php

namespace App\Livewire\Absensi;

use App\Models\Absensi;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ClockIn extends Component
{
    public function render()
    {
        $absensiHariIni = Absensi::where('user_id', Auth::id())
            ->where('tanggal', now()->toDateString())
            ->first();

        return view('livewire.absensi.clock-in', [
            'absensi' => $absensiHariIni
        ])->layout('layouts.absensi');
    }
}
