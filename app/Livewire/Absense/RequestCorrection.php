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

    public $isEditMode = false;
    public $editingId = null;
    public $viewingCorrection = null;

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

    public function viewCorrection($id)
    {
        $this->viewingCorrection = null;
        $this->viewingCorrection = AttendanceCorrection::where('user_id', Auth::id())->findOrFail($id);
        $this->dispatch('open-modal', name: 'view-correction-detail');
    }

    public function editCorrection($id)
    {
        $cor = AttendanceCorrection::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->findOrFail($id);

        $this->isEditMode = true;
        $this->editingId = $id;
        $this->tanggal = $cor->tanggal;
        $this->jam_masuk_baru = $cor->jam_masuk_baru ? \Carbon\Carbon::parse($cor->jam_masuk_baru)->format('H:i') : null;
        $this->jam_keluar_baru = $cor->jam_keluar_baru ? \Carbon\Carbon::parse($cor->jam_keluar_baru)->format('H:i') : null;
        $this->alasan = $cor->alasan;
        $this->resetErrorBag();
    }

    public function cancelEdit()
    {
        $this->isEditMode = false;
        $this->editingId = null;
        $this->tanggal = now()->format('Y-m-d');
        $this->jam_masuk_baru = null;
        $this->jam_keluar_baru = null;
        $this->alasan = '';
        $this->resetErrorBag();
    }

    public function deleteCorrection($id)
    {
        $cor = AttendanceCorrection::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->findOrFail($id);

        $cor->delete();
        $this->dispatch('showToast', type: 'info', message: 'Permohonan perbaikan kehadiran berhasil dibatalkan.');
    }

    public function submit()
    {
        $this->validate();

        // Find existing attendance for that date if any
        $existing = Absensi::where('user_id', Auth::id())
            ->where('tanggal', $this->tanggal)
            ->first();

        if ($this->isEditMode) {
            $cor = AttendanceCorrection::where('user_id', Auth::id())
                ->where('status', 'pending')
                ->findOrFail($this->editingId);

            $cor->update([
                'absensi_id' => $existing ? $existing->id : null,
                'tanggal' => $this->tanggal,
                'jam_masuk_baru' => $this->jam_masuk_baru,
                'jam_keluar_baru' => $this->jam_keluar_baru,
                'alasan' => $this->alasan,
            ]);

            $this->cancelEdit();
            $this->dispatch('showToast', type: 'success', message: 'Permohonan perbaikan kehadiran berhasil diperbarui.');
        } else {
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
