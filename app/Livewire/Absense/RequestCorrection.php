<?php

namespace App\Livewire\Absense;

use App\Models\AttendanceCorrection;
use App\Models\Absensi;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class RequestCorrection extends Component
{
    use WithPagination;

    public $tanggal;
    public $jam_masuk_baru;
    public $jam_keluar_baru;
    public $alasan;
    public $absensi_id;

    protected $rules = [
        'tanggal' => 'required|date|before_or_equal:today',
        'jam_masuk_baru' => 'nullable|date_format:H:i',
        'jam_keluar_baru' => 'nullable|date_format:H:i',
        'alasan' => 'required|string|min:10',
    ];

    public function mount()
    {
        $this->tanggal = now()->format('Y-m-d');
    }

    public function submit()
    {
        $this->validate();

        // Find existing attendance for that date if any
        $existing = Absensi::where('user_id', Auth::id())
            ->where('tanggal', $this->tanggal)
            ->first();

        AttendanceCorrection::create([
            'user_id' => Auth::id(),
            'absensi_id' => $existing ? $existing->id : null,
            'tanggal' => $this->tanggal,
            'jam_masuk_baru' => $this->jam_masuk_baru,
            'jam_keluar_baru' => $this->jam_keluar_baru,
            'alasan' => $this->alasan,
            'status' => 'pending',
        ]);

        $this->reset(['alasan', 'jam_masuk_baru', 'jam_keluar_baru']);
        $this->dispatch('showToast', type: 'success', message: 'Permohonan perbaikan kehadiran berhasil dikirim.');
    }

    public function render()
    {
        $corrections = AttendanceCorrection::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('livewire.absense.request-correction', [
            'corrections' => $corrections
        ])->layout('layouts.absensi', ['title' => 'Perbaikan Kehadiran']);
    }
}
