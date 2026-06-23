<?php

namespace App\Livewire\Absensi;

use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use WithFileUploads;

    public $name;

    public $avatar;

    public $showEditProfileModal = false;

    public function mount()
    {
        $this->name = Auth::user()->name;
    }

    public function openEditProfile()
    {
        $this->name = Auth::user()->name;
        $this->avatar = null;
        $this->resetErrorBag();
        $this->showEditProfileModal = true;
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|min:2|max:100',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $user = Auth::user();
        $updates = ['name' => trim($this->name)];

        if ($this->avatar) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $updates['avatar'] = $this->avatar->store('profile-photos', 'public');
        }

        $user->update($updates);
        $this->avatar = null;
        $this->showEditProfileModal = false;

        $this->dispatch('showToast', type: 'success', message: 'Profil berhasil diperbarui.');
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->to('/absen/login');
    }

    public function render()
    {
        $user = Auth::user();

        // Hitung persentase kehadiran dan total jam bulan ini
        $records = Absensi::where('user_id', $user->id)
            ->whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->get();

        $totalHariBulanIni = now()->daysInMonth; // Estimasi hari kerja maksimal
        $hariHadir = $records->where('status', '!=', 'izin')->count();
        
        $persentase = $totalHariBulanIni > 0 ? min(100, round(($hariHadir / 22) * 100)) : 0; // Asumsi 22 hari kerja standar

        $totalMinutes = 0;
        foreach ($records as $r) {
            if ($r->jam_masuk && $r->jam_keluar) {
                $m = Carbon::parse($r->jam_masuk);
                $k = Carbon::parse($r->jam_keluar);
                $totalMinutes += $m->diffInMinutes($k);
            }
        }
        $totalJamKerja = round($totalMinutes / 60);

        return view('livewire.absensi.profile', [
            'user'          => $user,
            'persentase'    => $persentase,
            'totalJamKerja' => $totalJamKerja,
            'hakCuti'       => $user->hak_cuti ?? 12,
        ])->layout('layouts.absensi');
    }
}
