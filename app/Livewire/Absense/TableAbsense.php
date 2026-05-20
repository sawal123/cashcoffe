<?php

namespace App\Livewire\Absense;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class TableAbsense extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $search = '';
    public $perPage = 10;

    protected $queryString = ['search', 'page'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public $userIdForAbsen;
    public $selectedUserName;
    public $shiftId;
    public $status = 'hadir';
    public $jamMasuk;
    public $jamKeluar;
    public $keterangan;
    public $absensiId;
    public $fotoUrl;
    public $lokasiStr;
    public $isEdit = false;
    
    public $showDetailModal = false;
    public $selectedAbsenDetail = null;

    public function showDetail($userId)
    {
        $this->selectedAbsenDetail = \App\Models\Absensi::with(['user.jabatan', 'userShift.shift'])
            ->where('user_id', $userId)
            ->where(function($q) {
                $q->whereDate('created_at', \Carbon\Carbon::today())
                  ->orWhereDate('tanggal', \Carbon\Carbon::today());
            })
            ->first();

        if (!$this->selectedAbsenDetail) {
            $this->dispatch('alert', ['type' => 'info', 'message' => 'Karyawan ini belum absen hari ini.']);
            $this->dispatch('showToast', message: 'Karyawan ini belum absen hari ini.', type: 'info', title: 'Info');
            $this->showDetailModal = false;
            return;
        }

        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedAbsenDetail = null;
    }

    public function openTambahAbsen($userId)
    {
        $user = User::findOrFail($userId);
        $this->userIdForAbsen = $userId;
        $this->selectedUserName = $user->name;
        $this->isEdit = false;
        
        $userShift = \App\Models\UserShift::where('user_id', $userId)
            ->whereDate('tanggal', now()->toDateString())
            ->first();
            
        if ($userShift) {
            $this->shiftId = $userShift->shift_id;
            $this->jamMasuk = \Carbon\Carbon::parse($userShift->shift->jam_masuk)->format('H:i');
            $this->jamKeluar = \Carbon\Carbon::parse($userShift->shift->jam_keluar)->format('H:i');
        } else {
            $firstShift = \App\Models\Shift::first();
            $this->shiftId = $firstShift?->id;
            $this->jamMasuk = $firstShift ? \Carbon\Carbon::parse($firstShift->jam_masuk)->format('H:i') : '09:00';
            $this->jamKeluar = $firstShift ? \Carbon\Carbon::parse($firstShift->jam_keluar)->format('H:i') : '17:00';
        }
        
        $this->status = 'hadir';
        $this->keterangan = '';
        
        $this->dispatch('open-modal', name: 'modal-tambah-absen');
    }

    public function storeManualAbsen()
    {
        $this->validate([
            'shiftId' => 'required|exists:shifts,id',
            'status' => 'required|string',
            'jamMasuk' => 'nullable',
            'jamKeluar' => 'nullable',
            'keterangan' => 'nullable|string',
        ]);

        $denda = 0;
        if ($this->status === 'tidak clock out') {
            $shift = \App\Models\Shift::find($this->shiftId);
            $denda = $shift ? $shift->denda_missing_clockout : 0;
        }

        \App\Models\Absensi::create([
            'user_id' => $this->userIdForAbsen,
            'shift_id' => $this->shiftId,
            'tanggal' => now()->toDateString(),
            'status' => $this->status,
            'jam_masuk' => $this->jamMasuk ?: null,
            'jam_keluar' => $this->jamKeluar ?: null,
            'keterangan' => $this->keterangan,
            'denda_missing_clockout' => $denda,
        ]);

        $this->dispatch('close-modal', name: 'modal-tambah-absen');
        $this->dispatch('showToast', message: 'Berhasil menambahkan riwayat absen hari ini.', type: 'success', title: 'Success');
    }

    public function openDetailHariIni($absensiId)
    {
        $absen = \App\Models\Absensi::with('user')->findOrFail($absensiId);
        $this->absensiId = $absen->id;
        $this->userIdForAbsen = $absen->user_id;
        $this->selectedUserName = $absen->user->name;
        $this->shiftId = $absen->shift_id;
        $this->status = $absen->status;
        $this->jamMasuk = $absen->jam_masuk;
        $this->jamKeluar = $absen->jam_keluar;
        $this->keterangan = $absen->keterangan;
        $this->fotoUrl = $absen->foto ? asset('storage/' . $absen->foto) : null;
        $this->lokasiStr = $absen->lokasi;
        $this->isEdit = true;
        
        $this->dispatch('open-modal', name: 'modal-detail-absen');
    }

    public function updateManualAbsen()
    {
        $this->validate([
            'shiftId' => 'required|exists:shifts,id',
            'status' => 'required|string',
            'jamMasuk' => 'nullable',
            'jamKeluar' => 'nullable',
            'keterangan' => 'nullable|string',
        ]);

        $denda = 0;
        if ($this->status === 'tidak clock out') {
            $shift = \App\Models\Shift::find($this->shiftId);
            $denda = $shift ? $shift->denda_missing_clockout : 0;
        }

        $absen = \App\Models\Absensi::findOrFail($this->absensiId);
        $absen->update([
            'shift_id' => $this->shiftId,
            'status' => $this->status,
            'jam_masuk' => $this->jamMasuk ?: null,
            'jam_keluar' => $this->jamKeluar ?: null,
            'keterangan' => $this->keterangan,
            'denda_missing_clockout' => $denda,
        ]);

        $this->dispatch('close-modal', name: 'modal-detail-absen');
        $this->dispatch('showToast', message: 'Berhasil memperbarui riwayat absen hari ini.', type: 'success', title: 'Success');
    }

    public function deleteManualAbsen()
    {
        \App\Models\Absensi::destroy($this->absensiId);
        
        $this->dispatch('close-modal', name: 'modal-detail-absen');
        $this->dispatch('showToast', message: 'Berhasil menghapus riwayat absen hari ini.', type: 'success', title: 'Success');
    }

    public function render()
    {
        $today = now()->toDateString();

        $usersQuery = User::whereHas('roles', fn($q) => $q->where('name', 'karyawan'));
        
        $totalKaryawan = (clone $usersQuery)->count();
        $hadirCount = \App\Models\Absensi::where('tanggal', $today)->count();
        $terlambatCount = \App\Models\Absensi::where('tanggal', $today)->where('status', 'terlambat')->count();
        $belumAbsenCount = $totalKaryawan - $hadirCount;

        $users = $usersQuery
            ->where('name', 'like', '%' . $this->search . '%')
            ->with(['absensis' => function ($q) use ($today) {
                $q->where('tanggal', $today);
            }, 'roles'])
            ->paginate($this->perPage);

        $shifts = \App\Models\Shift::all();

        return view('livewire.absense.table-absense', [
            'users' => $users,
            'shifts' => $shifts,
            'stats' => [
                'total' => $totalKaryawan,
                'hadir' => $hadirCount,
                'terlambat' => $terlambatCount,
                'belum' => $belumAbsenCount,
            ],
            'title' => 'Monitoring Absensi Karyawan'
        ])->layout('layouts.app', ['title' => 'Absensi']);
    }
}
