<?php

namespace App\Livewire\Absensi;

use Carbon\Carbon;
use App\Models\Absensi;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Profile extends Component
{
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
