<?php

namespace App\Livewire\Absense;

use App\Models\IzinAbsensi;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class RequestLeave extends Component
{
    use WithFileUploads, WithPagination;

    public $tanggal_mulai;

    public $tanggal_selesai;

    public $jenis = 'izin';

    public $alasan;

    public $bukti;

    public $isEditMode = false;
    public $editingId = null;

    public function viewRequest($id)
    {
        $this->viewingRequest = null;
        $this->viewingRequest = IzinAbsensi::where('user_id', Auth::id())->findOrFail($id);
        $this->dispatch('open-modal', name: 'view-request-detail');
    }

    protected $rules = [
        'tanggal_mulai' => 'required|date|after_or_equal:today',
        'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        'jenis' => 'required|in:izin,sakit,cuti',
        'alasan' => 'required|string|min:10',
        'bukti' => 'nullable|image|max:2048',
    ];

    public function mount()
    {
        $this->tanggal_mulai = now()->format('Y-m-d');
        $this->tanggal_selesai = now()->format('Y-m-d');
    }

    public function editRequest($id)
    {
        $req = IzinAbsensi::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->findOrFail($id);

        $this->isEditMode = true;
        $this->editingId = $id;
        $this->tanggal_mulai = $req->tanggal_mulai;
        $this->tanggal_selesai = $req->tanggal_selesai;
        $this->jenis = $req->jenis;
        $this->alasan = $req->alasan;
        $this->bukti = null;
        $this->resetErrorBag();
    }

    public function cancelEdit()
    {
        $this->isEditMode = false;
        $this->editingId = null;
        $this->tanggal_mulai = now()->format('Y-m-d');
        $this->tanggal_selesai = now()->format('Y-m-d');
        $this->jenis = 'izin';
        $this->alasan = '';
        $this->bukti = null;
        $this->resetErrorBag();
    }

    public function deleteRequest($id)
    {
        $req = IzinAbsensi::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->findOrFail($id);

        if ($req->bukti) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($req->bukti);
        }

        $req->delete();
        $this->dispatch('showToast', type: 'info', message: 'Pengajuan izin/cuti berhasil dibatalkan.');
    }

    public function submit()
    {
        $this->validate();

        $user = Auth::user();
        if ($this->jenis === 'cuti') {
            if ($user->jatah_cuti < $this->total_hari) {
                $this->dispatch('showToast', type: 'error', message: 'Gagal! Sisa jatah cuti Anda tidak mencukupi (Sisa: '.$user->jatah_cuti.' hari, Pengajuan: '.$this->total_hari.' hari).');

                return;
            }
        }

        if ($this->isEditMode) {
            $req = IzinAbsensi::where('user_id', $user->id)
                ->where('status', 'pending')
                ->findOrFail($this->editingId);

            $updateData = [
                'tanggal_mulai' => $this->tanggal_mulai,
                'tanggal_selesai' => $this->tanggal_selesai,
                'jenis' => $this->jenis,
                'alasan' => $this->alasan,
            ];

            if ($this->bukti) {
                if ($req->bukti) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($req->bukti);
                }
                $updateData['bukti'] = $this->bukti->store('izin_absensi', 'public');
            }

            $req->update($updateData);

            $this->cancelEdit();
            $this->dispatch('showToast', type: 'success', message: 'Pengajuan izin/cuti berhasil diperbarui.');
        } else {
            $buktiPath = null;
            if ($this->bukti) {
                $buktiPath = $this->bukti->store('izin_absensi', 'public');
            }

            IzinAbsensi::create([
                'user_id' => $user->id,
                'tanggal_mulai' => $this->tanggal_mulai,
                'tanggal_selesai' => $this->tanggal_selesai,
                'jenis' => $this->jenis,
                'alasan' => $this->alasan,
                'bukti' => $buktiPath,
                'status' => 'pending',
            ]);

            $this->reset(['alasan', 'bukti']);
            $this->dispatch('showToast', type: 'success', message: 'Permohonan izin/cuti berhasil dikirim.');
        }
    }

    public function getTotalHariProperty()
    {
        if ($this->tanggal_mulai && $this->tanggal_selesai) {
            try {
                $start = \Carbon\Carbon::parse($this->tanggal_mulai);
                $end = \Carbon\Carbon::parse($this->tanggal_selesai);
                if ($end->gte($start)) {
                    return $start->diffInDays($end) + 1;
                }
            } catch (\Exception $e) {
                return 0;
            }
        }

        return 0;
    }

    public function getIsQuotaExceededProperty()
    {
        if ($this->jenis === 'cuti' && $this->total_hari > 0) {
            return Auth::user()->jatah_cuti < $this->total_hari;
        }

        return false;
    }

    public function render()
    {
        $requests = IzinAbsensi::where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('livewire.absense.request-leave', [
            'requests' => $requests,
        ])->layout('layouts.absensi', ['title' => 'Pengajuan Izin & Cuti']);
    }
}
