<?php

namespace App\Livewire\Absensi;

use Carbon\Carbon;
use App\Models\Absensi;
use Livewire\Component;
use Livewire\Attributes\On;

class Home extends Component
{

    public $today;
    public $time;
    public $absensiToday;

    public function mount()
    {

        Carbon::setLocale('id');
        $this->time = now()->format('H.i') . ' WIB';
        // Format: Sabtu, 06 Desember 2025
        $this->today = Carbon::now()->translatedFormat('l, d F Y');
        $this->absensiToday = Absensi::where('user_id', auth()->id())
            ->whereDate('tanggal', Carbon::today())
            ->first();
    }
    #[On('clock-in-data')]
    public function clockInData($data)
    {
        Absensi::create([
            'user_id'   => auth()->id(),
            'tanggal'   => now()->toDateString(),
            'jam_masuk' => now()->format('H:i:s'),
            'foto'      => $data['foto'],
            'lokasi'    => $data['lokasi'],
            'status'    => 'masuk',
        ]);

        session()->flash('success', 'Clock In berhasil!');
    }

    // public $shift;

    // public function getIsLateAttribute()
    // {
    //     if (!$this->jam_masuk) return false;

    //     $shift = \App\Models\Shift::first();
    //     if (!$shift) return false;

    //     return \Carbon\Carbon::parse($this->jam_masuk)
    //         ->gt(\Carbon\Carbon::parse($shift->jam_masuk));
    // }

    public function render()
    {
           $shift = \App\Models\Shift::first();
        return view('livewire.absensi.home', compact('shift'))->layout('layouts.absensi');
    }
}
