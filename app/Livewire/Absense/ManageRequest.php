<?php

namespace App\Livewire\Absense;

use App\Models\IzinAbsensi;
use App\Models\AttendanceCorrection;
use App\Models\Absensi;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class ManageRequest extends Component
{
    use WithPagination;

    #[Url]
    public $type = 'leave'; // 'leave' or 'correction'

    #[Url]
    public $jenis = 'cuti'; // 'cuti', 'izin' or 'all'

    public $selectedId;

    public function setType($type)
    {
        $this->type = $type;
        if ($type === 'correction') {
            $this->jenis = 'all';
        }
        $this->resetPage();
    }

    public function setTab($type, $jenis)
    {
        $this->type = $type;
        $this->jenis = $jenis;
        $this->resetPage();
    }

    public function approveLeave($id)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
                $req = IzinAbsensi::lockForUpdate()->findOrFail($id);
                
                if ($req->status !== 'pending') {
                    throw new \Exception('Pengajuan ini sudah pernah diproses.');
                }

                $user = \App\Models\User::lockForUpdate()->findOrFail($req->user_id);

                if (trim(strtolower($req->jenis)) === 'cuti') {
                    $start = \Carbon\Carbon::parse($req->tanggal_mulai);
                    $end = \Carbon\Carbon::parse($req->tanggal_selesai);
                    $days = $start->diffInDays($end) + 1;

                    if ($user->jatah_cuti < $days) {
                        throw new \Exception('Gagal! Saldo jatah cuti tidak mencukupi (Saldo: ' . $user->jatah_cuti . ' hari, Pengajuan: ' . $days . ' hari).');
                    }

                    // Potong jatah cuti (akumulatif)
                    $user->decrement('jatah_cuti', $days);
                }

                // Buat record di tabel absensis agar terhitung hadir/izin/cuti
                $start = \Carbon\Carbon::parse($req->tanggal_mulai);
                $end = \Carbon\Carbon::parse($req->tanggal_selesai);
                
                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    Absensi::updateOrCreate(
                        ['user_id' => $req->user_id, 'tanggal' => $date->format('Y-m-d')],
                        ['status' => $req->jenis, 'keterangan' => $req->alasan]
                    );
                }

                $req->update([
                    'status' => 'approved',
                    'approved_by' => Auth::id()
                ]);
            });

            $this->dispatch('showToast', type: 'success', message: 'Pengajuan berhasil disetujui.');

        } catch (\Exception $e) {
            $this->dispatch('showToast', type: 'error', message: $e->getMessage());
        }
    }

    public function cancelLeave($id)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
                $req = IzinAbsensi::lockForUpdate()->findOrFail($id);
                
                if ($req->status !== 'approved') {
                    throw new \Exception('Hanya pengajuan yang sudah disetujui yang dapat dibatalkan.');
                }

                $user = \App\Models\User::lockForUpdate()->findOrFail($req->user_id);

                if (trim(strtolower($req->jenis)) === 'cuti') {
                    $start = \Carbon\Carbon::parse($req->tanggal_mulai);
                    $end = \Carbon\Carbon::parse($req->tanggal_selesai);
                    $days = $start->diffInDays($end) + 1;

                    // Kembalikan saldo jatah cuti
                    $user->increment('jatah_cuti', $days);
                }

                // Hapus record absensi yang terkait (untuk hari libur/cuti tersebut)
                $start = \Carbon\Carbon::parse($req->tanggal_mulai);
                $end = \Carbon\Carbon::parse($req->tanggal_selesai);
                
                Absensi::where('user_id', $req->user_id)
                    ->whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->where('status', $req->jenis)
                    ->delete();

                $req->update([
                    'status' => 'pending',
                    'approved_by' => null
                ]);
            });

            $this->dispatch('showToast', type: 'info', message: 'Persetujuan dibatalkan. Status kembali ke pending.');

        } catch (\Exception $e) {
            $this->dispatch('showToast', type: 'error', message: $e->getMessage());
        }
    }

    public function rejectLeave($id)
    {
        $req = IzinAbsensi::findOrFail($id);
        $req->update(['status' => 'rejected']);
        $this->dispatch('showToast', type: 'warning', message: 'Pengajuan izin ditolak.');
    }

    public function approveCorrection($id)
    {
        $cor = AttendanceCorrection::findOrFail($id);
        
        // Update or Create Absensi record
        Absensi::updateOrCreate(
            ['user_id' => $cor->user_id, 'tanggal' => $cor->tanggal],
            [
                'jam_masuk' => $cor->jam_masuk_baru,
                'jam_keluar' => $cor->jam_keluar_baru,
                'status' => 'hadir' // Or calculate status based on shift if needed
            ]
        );

        $cor->update([
            'status' => 'approved',
            'approved_by' => Auth::id()
        ]);

        $this->dispatch('showToast', type: 'success', message: 'Perbaikan kehadiran disetujui dan data diperbarui.');
    }

    public function rejectCorrection($id)
    {
        $cor = AttendanceCorrection::findOrFail($id);
        $cor->update(['status' => 'rejected']);
        $this->dispatch('showToast', type: 'warning', message: 'Perbaikan kehadiran ditolak.');
    }

    public function render()
    {
        $leavesQuery = IzinAbsensi::with('user')->latest();
        if ($this->jenis === 'cuti') {
            $leavesQuery->where('jenis', 'cuti');
        } elseif ($this->jenis === 'izin') {
            $leavesQuery->whereIn('jenis', ['izin', 'sakit']);
        }
        $leaves = $leavesQuery->paginate(10);

        $corrections = AttendanceCorrection::with('user')->latest()->paginate(10);

        $title = 'Kelola Pengajuan Kehadiran';
        if ($this->type === 'correction') {
            $title = 'Persetujuan Perbaikan Absensi';
        } elseif ($this->type === 'leave') {
            if ($this->jenis === 'cuti') {
                $title = 'Persetujuan Cuti';
            } elseif ($this->jenis === 'izin') {
                $title = 'Persetujuan Izin';
            }
        }

        return view('livewire.absense.manage-request', [
            'leaves' => $leaves,
            'corrections' => $corrections
        ])->layout('layouts.app', ['title' => $title]);
    }
}
