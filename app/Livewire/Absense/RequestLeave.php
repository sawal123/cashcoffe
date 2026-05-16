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

    public $viewingRequest;

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

    public function submit()
    {
        $this->validate();

        $user = Auth::user();
        if ($this->jenis === 'cuti') {
            if ($user->hak_cuti < $this->total_hari) {
                $this->dispatch('showToast', type: 'error', message: 'Gagal! Sisa hak cuti Anda tidak mencukupi (Sisa: '.$user->hak_cuti.' hari, Pengajuan: '.$this->total_hari.' hari).');

                return;
            }
        }

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
            return Auth::user()->hak_cuti < $this->total_hari;
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
