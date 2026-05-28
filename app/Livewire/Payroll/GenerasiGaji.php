<?php

namespace App\Livewire\Payroll;

use App\Models\User;
use App\Models\Absensi;
use App\Models\Payroll;
use App\Models\UserShift;
use App\Services\PayrollService;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class GenerasiGaji extends Component
{
    public $month;
    public $year;
    public $detailTitle = '';
    public $detailType = '';
    public $detailRows = [];
    public $showDetailModal = false;

    public function mount()
    {
        $this->month = now()->month;
        $this->year = now()->year;
    }

    public function hitungGajiMassal()
    {
        $payrollService = new PayrollService();
        $employees = User::role('karyawan')->get();

        if ($employees->isEmpty()) {
            $this->dispatch('showToast', message: 'Tidak ada karyawan aktif dengan role karyawan.', type: 'warning', title: 'Peringatan');
            return;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($employees as $employee) {
            try {
                $payrollService->hitungGajiBulanan($employee->id, (int)$this->year, (int)$this->month);
                $successCount++;
            } catch (\Exception $e) {
                Log::error("Error generating payroll for User ID {$employee->id} in {$this->month}/{$this->year}: " . $e->getMessage());
                $failCount++;
            }
        }

        if ($failCount > 0) {
            $this->dispatch('showToast', message: "Kalkulasi selesai. Berhasil: {$successCount}, Gagal: {$failCount}.", type: 'warning', title: 'Selesai dengan error');
        } else {
            $this->dispatch('showToast', message: "Berhasil menghitung gaji massal untuk {$successCount} karyawan.", type: 'success', title: 'Sukses');
        }
    }

    public function showDoubleShiftDetails($payrollId)
    {
        $payroll = Payroll::with('user')->findOrFail($payrollId);

        if (!$payroll->user) {
            $this->openDetailModal('Rincian Double Shift', 'double_shift', []);
            return;
        }

        $nilaiHarian = $payroll->gaji_pokok / PayrollService::DIVIDER_HARI_KERJA;

        $rows = UserShift::with('shift')
            ->where('user_id', $payroll->user_id)
            ->whereBetween('tanggal', [
                $payroll->periode_mulai->toDateString(),
                $payroll->periode_selesai->toDateString(),
            ])
            ->whereDate('tanggal', '<=', now()->toDateString())
            ->where('is_double_shift', true)
            ->whereHas('shift', function ($query) {
                $query->where('nama_shift', 'like', '%double%');
            })
            ->orderBy('tanggal')
            ->get()
            ->map(function ($userShift) use ($nilaiHarian) {
                return [
                    'tanggal' => $userShift->tanggal->format('d M Y'),
                    'status' => 'Double Shift',
                    'shift' => $userShift->shift->nama_shift ?? '-',
                    'nominal' => $nilaiHarian,
                    'keterangan' => 'Bonus 1x nilai harian',
                ];
            })
            ->values()
            ->toArray();

        $this->openDetailModal('Rincian Double Shift - ' . $payroll->user->name, 'double_shift', $rows);
    }

    public function showDeductionDetails($payrollId)
    {
        $payroll = Payroll::with('user')->findOrFail($payrollId);

        if (!$payroll->user) {
            $this->openDetailModal('Rincian Potongan', 'deduction', []);
            return;
        }

        $nilaiHarian = $payroll->gaji_pokok / PayrollService::DIVIDER_HARI_KERJA;

        $absensis = Absensi::with('shift')
            ->where('user_id', $payroll->user_id)
            ->whereBetween('tanggal', [
                $payroll->periode_mulai->toDateString(),
                $payroll->periode_selesai->toDateString(),
            ])
            ->get();

        $absensisBySchedule = $absensis->keyBy(function ($absensi) {
            return $absensi->tanggal . '-' . ($absensi->shift_id ?? 'none');
        });

        $scheduledAlphaRows = UserShift::with('shift')
            ->where('user_id', $payroll->user_id)
            ->whereBetween('tanggal', [
                $payroll->periode_mulai->toDateString(),
                $payroll->periode_selesai->toDateString(),
            ])
            ->whereDate('tanggal', '<', now()->toDateString())
            ->orderBy('tanggal')
            ->get()
            ->filter(function ($userShift) use ($absensisBySchedule) {
                $key = $userShift->tanggal->toDateString() . '-' . $userShift->shift_id;
                $absensi = $absensisBySchedule->get($key);

                return !$absensi || $absensi->status === 'alpha';
            })
            ->map(function ($userShift) use ($nilaiHarian) {
                return [
                    'sort_date' => $userShift->tanggal->toDateString(),
                    'key' => $userShift->tanggal->toDateString() . '-' . $userShift->shift_id,
                    'tanggal' => $userShift->tanggal->format('d M Y'),
                    'status' => 'Alpha',
                    'shift' => $userShift->shift->nama_shift ?? '-',
                    'nominal' => $nilaiHarian,
                    'keterangan' => 'Potongan 1x nilai harian',
                ];
            });

        $scheduledAlphaKeys = collect($scheduledAlphaRows->pluck('key')->all());

        $explicitAlphaRows = $absensis
            ->where('status', 'alpha')
            ->filter(function ($absensi) {
                return Carbon::parse($absensi->tanggal)->lt(now()->startOfDay());
            })
            ->reject(function ($absensi) use ($scheduledAlphaKeys) {
                $key = $absensi->tanggal . '-' . ($absensi->shift_id ?? 'none');

                return $scheduledAlphaKeys->contains($key);
            })
            ->map(function ($absensi) use ($nilaiHarian) {
                return [
                    'sort_date' => $absensi->tanggal,
                    'key' => $absensi->tanggal . '-' . ($absensi->shift_id ?? 'none'),
                    'tanggal' => Carbon::parse($absensi->tanggal)->format('d M Y'),
                    'status' => 'Alpha',
                    'shift' => $absensi->shift->nama_shift ?? '-',
                    'nominal' => $nilaiHarian,
                    'keterangan' => $absensi->keterangan ?: 'Potongan 1x nilai harian',
                ];
            });

        $lateRows = $absensis
            ->where('status', 'terlambat')
            ->map(function ($absensi) {
                return [
                    'sort_date' => $absensi->tanggal,
                    'tanggal' => Carbon::parse($absensi->tanggal)->format('d M Y'),
                    'status' => 'Telat',
                    'shift' => $absensi->shift->nama_shift ?? '-',
                    'nominal' => $absensi->shift->denda_telat ?? PayrollService::DENDA_TERLAMBAT,
                    'keterangan' => $absensi->keterangan ?: '-',
                ];
            });

        $missingClockOutRows = $absensis
            ->where('status', 'tidak clock out')
            ->map(function ($absensi) {
                $nominal = $absensi->denda_missing_clockout;
                if (($nominal === null || $nominal == 0) && $absensi->shift) {
                    $nominal = $absensi->shift->denda_missing_clockout;
                }

                return [
                    'sort_date' => $absensi->tanggal,
                    'tanggal' => Carbon::parse($absensi->tanggal)->format('d M Y'),
                    'status' => 'Lupa Clock Out',
                    'shift' => $absensi->shift->nama_shift ?? '-',
                    'nominal' => $nominal ?? 0,
                    'keterangan' => $absensi->keterangan ?: '-',
                ];
            });

        $rows = collect($scheduledAlphaRows->all())
            ->concat($explicitAlphaRows->all())
            ->concat($lateRows->all())
            ->concat($missingClockOutRows->all())
            ->sortBy('sort_date')
            ->map(function ($row) {
                unset($row['sort_date']);
                unset($row['key']);

                return $row;
            })
            ->values()
            ->toArray();

        $this->openDetailModal('Rincian Potongan - ' . $payroll->user->name, 'deduction', $rows);
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->detailRows = [];
    }

    private function openDetailModal($title, $type, array $rows)
    {
        $this->detailTitle = $title;
        $this->detailType = $type;
        $this->detailRows = $rows;
        $this->showDetailModal = true;
    }

    public function render()
    {
        $payrollService = new PayrollService();
        [$startDateCarbon, $endDateCarbon] = $payrollService->getCutoffPeriod((int)$this->year, (int)$this->month);
        $startDate = $startDateCarbon->toDateString();
        $endDate = $endDateCarbon->toDateString();

        $payrolls = Payroll::with(['user.jabatan'])
            ->where('periode_mulai', $startDate)
            ->where('periode_selesai', $endDate)
            ->get();

        return view('livewire.payroll.generasi-gaji', [
            'payrolls' => $payrolls,
            'startDateFormatted' => $startDateCarbon->format('d M Y'),
            'endDateFormatted' => $endDateCarbon->format('d M Y'),
            'periodOptions' => $this->periodOptions(),
        ])->layout('layouts.app', ['title' => 'Generasi Gaji Bulanan']);
    }

    private function periodOptions()
    {
        return collect(range(1, 12))->map(function ($month) {
            $start = Carbon::createFromDate((int) $this->year, $month, 1)->locale('id');
            $end = $start->copy()->addMonthNoOverflow();

            return [
                'value' => $month,
                'label' => $start->translatedFormat('F') . '-' . $end->translatedFormat('F'),
            ];
        });
    }
}
