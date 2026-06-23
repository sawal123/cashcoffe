<?php

namespace App\Livewire\Absense;

use App\Models\Absensi;
use App\Models\AttendanceCorrection;
use App\Models\IzinAbsensi;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;

class ShowAbsense extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $userId;

    #[Url]
    public $month;

    #[Url]
    public $year;

    #[Url(as: 'date')]
    public $highlightDate;

    public $selected, $alamatMasuk, $alamatKeluar;
    public $selectedApprovals = [];

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

        $denda = 0;
        if ($this->status === 'tidak clock out') {
            $absen = Absensi::findOrFail($this->selectedId);
            $denda = $absen->shift ? $absen->shift->denda_missing_clockout : 0;
        }

        Absensi::where('id', $this->selectedId)->update([
            'status' => $this->status,
            'keterangan' => $this->keterangan,
            'denda_missing_clockout' => $denda,
        ]);

        $this->reset(['selectedId', 'status', 'keterangan']);

        $this->dispatch('close-modal', name: 'update-status');
        $this->dispatch('showToast', message: 'Status absensi berhasil diperbarui', type: 'success', title: 'Success');

        session()->flash('success', 'Status absensi berhasil diperbarui');
    }

    public function loadAbsenseDetail($id)
    {
        $this->selected = Absensi::findOrFail($id);
        $this->selectedApprovals = $this->approvalEventsForDate($this->selected->tanggal);
        $this->alamatMasuk = null;
        $this->alamatKeluar = null;

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
        $this->month = $this->month ?: now()->month;
        $this->year = $this->year ?: now()->year;
    }



    public function render()
    {
        $user  = User::findOrFail($this->userId);
        $shift = \App\Models\Shift::first();

        $month = (int) $this->month;
        $year  = (int) $this->year;

        $today = now();
        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $endDay = ($today->month == $month && $today->year == $year)
            ? $today->day
            : \Carbon\Carbon::create($year, $month)->daysInMonth;

        // Ambil absensi sebulan
        $absensisCollection = Absensi::where('user_id', $user->id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->get()
            ->keyBy(fn($item) => $item->tanggal);

        $approvalEventsByDate = $this->approvalEventsForPeriod($monthStart, $monthEnd);

        // ================================
        // SUMMARY INIT
        // ================================
        $totalHadir     = 0;
        $totalTerlambat = 0;
        $totalIzin      = 0;
        $totalSakit     = 0;
        $totalCuti      = 0;
        $totalAlpha     = 0;
        $totalTidakClockOut = 0;

        $calendar = collect();

        for ($day = 1; $day <= $endDay; $day++) {
            $tanggalObj = \Carbon\Carbon::create($year, $month, $day);
            $tanggal    = $tanggalObj->toDateString();

            $item = $absensisCollection->get($tanggal);

            if ($item) {
                switch ($item->status) {

                    case 'hadir':
                        $totalHadir++;
                        break;

                    case 'complete':
                        $totalHadir++;
                        break;

                    case 'terlambat':
                        $totalHadir++;
                        $totalTerlambat++;
                        break;

                    case 'tidak clock out':
                        $totalTidakClockOut++;
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
                'absen'   => $item,
                'approvals' => $approvalEventsByDate->get($tanggal, []),
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
            'totalTidakClockOut',
            'totalHari'
        ))->layout('layouts.app', ['title' => 'Detail Absensi']);
    }

    private function approvalEventsForDate($date): array
    {
        $day = Carbon::parse($date);

        return $this->approvalEventsForPeriod($day->copy()->startOfDay(), $day->copy()->endOfDay())
            ->get($day->toDateString(), []);
    }

    private function approvalEventsForPeriod(Carbon $start, Carbon $end)
    {
        $events = collect();

        AttendanceCorrection::with('approver')
            ->where('user_id', $this->userId)
            ->where('status', 'approved')
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->each(function ($correction) use ($events) {
                $date = Carbon::parse($correction->tanggal)->toDateString();
                $events->push([
                    'date' => $date,
                    'type' => 'correction',
                    'label' => 'Absensi diperbaiki',
                    'description' => $correction->alasan,
                    'approver' => $correction->approver?->name,
                    'approved_at' => $correction->updated_at,
                    'jam_masuk' => $correction->jam_masuk_baru,
                    'jam_keluar' => $correction->jam_keluar_baru,
                ]);
            });

        IzinAbsensi::with('approver')
            ->where('user_id', $this->userId)
            ->where('status', 'approved')
            ->whereDate('tanggal_mulai', '<=', $end->toDateString())
            ->whereDate('tanggal_selesai', '>=', $start->toDateString())
            ->get()
            ->each(function ($leave) use ($events, $start, $end) {
                $rangeStart = Carbon::parse($leave->tanggal_mulai)->max($start);
                $rangeEnd = Carbon::parse($leave->tanggal_selesai)->min($end);

                for ($date = $rangeStart->copy(); $date->lte($rangeEnd); $date->addDay()) {
                    $events->push([
                        'date' => $date->toDateString(),
                        'type' => 'leave',
                        'leave_type' => $leave->jenis,
                        'label' => ucfirst($leave->jenis).' disetujui',
                        'description' => $leave->alasan,
                        'approver' => $leave->approver?->name,
                        'approved_at' => $leave->updated_at,
                    ]);
                }
            });

        return $events->groupBy('date');
    }
}
