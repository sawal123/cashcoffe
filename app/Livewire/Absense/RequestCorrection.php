<?php

namespace App\Livewire\Absense;

use App\Models\AttendanceCorrection;
use App\Models\Absensi;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class RequestCorrection extends Component
{
    use WithPagination, WithFileUploads;

    public $tanggal;
    public $jam_masuk_baru;
    public $jam_keluar_baru;
    public $alasan;
    public $bukti;
    public $absensi_id;

    public $isEditMode = false;
    public $editingId = null;
    public $viewingCorrection = null;

    protected $rules = [
        'tanggal' => 'required|date|before_or_equal:today',
        'jam_masuk_baru' => 'nullable|date_format:H:i',
        'jam_keluar_baru' => 'nullable|date_format:H:i',
        'alasan' => 'required|string|min:10',
        'bukti' => 'nullable|image|max:2048',
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

    public function createCorrection()
    {
        $this->isEditMode = false;
        $this->editingId = null;
        $this->tanggal = now()->format('Y-m-d');
        $this->jam_masuk_baru = null;
        $this->jam_keluar_baru = null;
        $this->alasan = '';
        $this->bukti = null;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'form-request-correction');
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
        $this->bukti = null;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'form-request-correction');
    }

    public function cancelEdit()
    {
        $this->isEditMode = false;
        $this->editingId = null;
        $this->tanggal = now()->format('Y-m-d');
        $this->jam_masuk_baru = null;
        $this->jam_keluar_baru = null;
        $this->alasan = '';
        $this->bukti = null;
        $this->resetErrorBag();
        $this->dispatch('close-modal', name: 'form-request-correction');
    }

    public function deleteCorrection($id)
    {
        $cor = AttendanceCorrection::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->findOrFail($id);

        if ($cor->bukti) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($cor->bukti);
        }

        $cor->delete();
        $this->dispatch('showToast', type: 'info', message: 'Permohonan perbaikan kehadiran berhasil dibatalkan.');
    }

    public function submit()
    {
        if ($this->jam_masuk_baru === '') {
            $this->jam_masuk_baru = null;
        }
        if ($this->jam_keluar_baru === '') {
            $this->jam_keluar_baru = null;
        }

        $this->validate();

        // Find existing attendance for that date if any
        $existing = Absensi::where('user_id', Auth::id())
            ->where('tanggal', $this->tanggal)
            ->first();

        if ($this->isEditMode) {
            $cor = AttendanceCorrection::where('user_id', Auth::id())
                ->where('status', 'pending')
                ->findOrFail($this->editingId);

            $updateData = [
                'absensi_id' => $existing ? $existing->id : null,
                'tanggal' => $this->tanggal,
                'jam_masuk_baru' => $this->jam_masuk_baru,
                'jam_keluar_baru' => $this->jam_keluar_baru,
                'alasan' => $this->alasan,
            ];

            if ($this->bukti) {
                if ($cor->bukti) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($cor->bukti);
                }
                $updateData['bukti'] = $this->bukti->store('perbaikan_absensi', 'public');
            }

            $cor->update($updateData);

            $this->cancelEdit();
            $this->dispatch('showToast', type: 'success', message: 'Permohonan perbaikan kehadiran berhasil diperbarui.');
        } else {
            $buktiPath = null;
            if ($this->bukti) {
                $buktiPath = $this->bukti->store('perbaikan_absensi', 'public');
            }

            AttendanceCorrection::create([
                'user_id' => Auth::id(),
                'absensi_id' => $existing ? $existing->id : null,
                'tanggal' => $this->tanggal,
                'jam_masuk_baru' => $this->jam_masuk_baru,
                'jam_keluar_baru' => $this->jam_keluar_baru,
                'alasan' => $this->alasan,
                'bukti' => $buktiPath,
                'status' => 'pending',
            ]);

            $this->reset(['alasan', 'jam_masuk_baru', 'jam_keluar_baru', 'bukti']);
            $this->dispatch('close-modal', name: 'form-request-correction');
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
