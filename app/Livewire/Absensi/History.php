<?php

namespace App\Livewire\Absensi;

use Carbon\Carbon;
use App\Models\Absensi;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class History extends Component
{
    public $selectedMonth;

    public function mount()
    {
        Carbon::setLocale('id');
        $this->selectedMonth = now()->format('Y-m');
    }

    public function render()
    {
        $query = Absensi::where('user_id', Auth::id())
            ->orderBy('tanggal', 'desc');

        if ($this->selectedMonth) {
            $parts = explode('-', $this->selectedMonth);
            if (count($parts) === 2) {
                $query->whereYear('tanggal', $parts[0])
                      ->whereMonth('tanggal', $parts[1]);
            }
        }

        $records = $query->get();

        $totalKehadiran = $records->count();
        
        $totalMinutes = 0;
        $totalMasukSecs = 0;
        $countMasuk = 0;

        foreach ($records as $r) {
            if ($r->jam_masuk) {
                $countMasuk++;
                $tMasuk = Carbon::parse($r->jam_masuk);
                $totalMasukSecs += $tMasuk->secondsSinceMidnight();

                if ($r->jam_keluar) {
                    $tKeluar = Carbon::parse($r->jam_keluar);
                    $totalMinutes += $tMasuk->diffInMinutes($tKeluar);
                }
            }
        }

        $totalJamKerja = round($totalMinutes / 60, 1);
        $avgMasuk = $countMasuk > 0 ? gmdate("H:i", floor($totalMasukSecs / $countMasuk)) . ' WIB' : '--:--';

        $months = [];
        for ($i = 0; $i < 6; $i++) {
            $m = now()->subMonths($i);
            $months[$m->format('Y-m')] = $m->translatedFormat('F Y');
        }

        return view('livewire.absensi.history', [
            'records'        => $records,
            'totalKehadiran' => $totalKehadiran,
            'totalJamKerja'  => $totalJamKerja,
            'avgMasuk'       => $avgMasuk,
            'months'         => $months,
        ])->layout('layouts.absensi');
    }
}
