<?php

namespace App\Livewire\Absense;

use App\Models\User;
use App\Models\Absensi;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;

class ShowAbsense extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $userId;
    public $month;
    public $year;

    public $selected, $alamatMasuk, $alamatKeluar;

    public function loadAbsenseDetail($id)
    {
        $this->selected = Absensi::findOrFail($id);
        if ($this->selected?->lokasi) {
            $this->alamatMasuk = $this->getAlamat($this->selected->lokasi);
        }

        // Lokasi keluar
        if ($this->selected?->lokasi_keluar) {
            $this->alamatKeluar = $this->getAlamat($this->selected->lokasi_keluar);
        }
    }

    private function getAlamat($lokasi)
    {
        if (!str_contains($lokasi, ',')) {
            return null;
        }

        [$lat, $lng] = explode(',', $lokasi);

        $response = Http::withHeaders([
            'User-Agent' => 'AbsensiApp/1.0'
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => trim($lat),
            'lon' => trim($lng),
            'format' => 'json',
        ]);

        return $response->json('display_name');
    }
    public function mount($userId)
    {
        $this->userId = $userId;
        $this->month = now()->month;
        $this->year = now()->year;
    }

    public $totalHadir = 0;
    public $totalTerlambat = 0;
    public $totalAlpha = 0;
    public $totalHari = 0;

    public function loadSummary($userId)
    {
        $query = Absensi::where('user_id', $userId)
            ->whereMonth('tanggal', $this->month)
            ->whereYear('tanggal', $this->year);

        $this->totalHari = $query->count();

        $this->totalHadir = (clone $query)
            ->where('status', 'hadir')
            ->count();

        $this->totalTerlambat = (clone $query)
            ->where('status', 'terlambat')
            ->count();

        $this->totalAlpha = (clone $query)
            ->whereNull('jam_masuk')
            ->count();
    }

    public function render()
    {
        $user = User::findOrFail($this->userId);
        $shift = \App\Models\Shift::first();

        $absensis = Absensi::where('user_id', $this->userId)
            ->whereMonth('tanggal', $this->month)
            ->whereYear('tanggal', $this->year)
            ->orderBy('tanggal', 'asc')
            ->paginate(10);
        return view('livewire.absense.show-absense', compact('user', 'absensis', 'shift'));
    }
}
