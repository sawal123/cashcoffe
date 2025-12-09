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

    public $selectedId;
    public $status;
    public $keterangan;
    public function loadStatus($id)
    {
        $absen = Absensi::findOrFail($id);

        $this->selectedId = $id;
        $this->status = $absen->status;
        $this->keterangan = $absen->keterangan;
    }

    public function updateStatus()
    {
        $this->validate([
            'status' => 'required|string',
        ]);

        Absensi::where('id', $this->selectedId)->update([
            'status' => $this->status,
            'keterangan' => $this->keterangan,
        ]);

        $this->reset(['selectedId', 'status', 'keterangan']);

        $this->dispatch('close-modal', name: 'update-status');
        $this->dispatch('showToast', message: 'Status absensi berhasil diperbarui', type: 'success', title: 'Success');

        session()->flash('success', 'Status absensi berhasil diperbarui');
    }

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



    public function render()
    {
        $user  = User::findOrFail($this->userId);
        $shift = \App\Models\Shift::first();

        $month = $this->month;
        $year  = $this->year;

        $today = now();

        $endDay = ($today->month == $month && $today->year == $year)
            ? $today->day
            : \Carbon\Carbon::create($year, $month)->daysInMonth;

        // Ambil absensi sebulan
        $absensisCollection = Absensi::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->get()
            ->keyBy(fn($item) => $item->tanggal);

        // ================================
        // SUMMARY INIT
        // ================================
        $totalHadir     = 0;
        $totalTerlambat = 0;
        $totalIzin      = 0;
        $totalSakit     = 0;
        $totalCuti      = 0;
        $totalAlpha     = 0;

        $calendar = collect();

        for ($day = 1; $day <= $endDay; $day++) {
            $tanggalObj = \Carbon\Carbon::create($year, $month, $day);
            $tanggal    = $tanggalObj->toDateString();

            $item = $absensisCollection->get($tanggal);

            if ($item) {
                switch ($item->status) {

                    case 'complete':
                        $totalHadir++;
                        break;

                    case 'terlambat':
                        $totalHadir++;
                        $totalTerlambat++;
                        break;

                    case 'izin':
                        $totalIzin++;
                        break;

                    case 'sakit':
                        $totalSakit++;
                        break;

                    case 'cuti':
                        $totalCuti++;
                        break;

                    case 'wfh':
                    case 'dinas_luar':
                        $totalHadir++;
                        break;
                }
            } else {
                // Hari lampau tanpa data = ALPHA
                if ($tanggalObj->isPast()) {
                    $totalAlpha++;
                }
            }

            $calendar->push([
                'tanggal' => $tanggal,
                'absen'   => $item
            ]);
        }

        $totalHari = $calendar->count();

        return view('livewire.absense.show-absense', compact(
            'user',
            'shift',
            'calendar',
            'totalHadir',
            'totalTerlambat',
            'totalIzin',
            'totalSakit',
            'totalCuti',
            'totalAlpha',
            'totalHari'
        ));
    }
}
