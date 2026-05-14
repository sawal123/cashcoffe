<?php

namespace App\Livewire\Absensi;

use Carbon\Carbon;
use App\Models\Absensi;
use Livewire\Component;

class Home extends Component
{

    public $today;
    public $time;
    public $absensiToday;

    public function mount()
    {
        Carbon::setLocale('id');
        $this->time = now()->format('H.i') . ' WIB';
        $this->today = Carbon::now()->translatedFormat('l, d F Y');
        $this->absensiToday = Absensi::where('user_id', auth()->id())
            ->whereDate('tanggal', Carbon::today())
            ->first();
    }

    public function render()
    {
        $shift = \App\Models\Shift::first();
        return view('livewire.absensi.home', compact('shift'))->layout('layouts.absensi');
    }
}
