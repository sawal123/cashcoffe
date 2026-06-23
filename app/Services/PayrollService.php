<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\AttendanceCorrection;
use App\Models\IzinAbsensi;
use App\Models\User;
use App\Models\UserShift;
use Carbon\Carbon;

class PayrollService
{
    /**
     * Tetapkan konstanta pembagi gaji prorate sesuai permintaan user.
     */
    const DIVIDER_HARI_KERJA = 25;

    const DENDA_TERLAMBAT = 20000;

    public function getCutoffPeriod(int $year, int $month): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->day(26)->startOfDay();
        $endDate = $startDate->copy()->addMonthNoOverflow()->day(25)->endOfDay();

        return [$startDate, $endDate];
    }

    /**
     * Menghitung kalkulasi payroll bulanan per user_id.
     * Periode: Tanggal 26 bulan terpilih s/d Tanggal 25 bulan berikutnya.
     *
     * @param  int  $userId
     * @param  Carbon|null  $endDate  Akhir periode cut-off
     * @return array
     */
    public function calculate($userId, ?Carbon $endDate = null)
    {
        if (! $endDate) {
            [$startDate, $endDate] = $this->getCutoffPeriod((int) now()->year, (int) now()->month);
        } else {
            $endDate = $endDate->copy()->endOfDay();
            $startDate = $endDate->copy()->subMonthNoOverflow()->day(26)->startOfDay();
        }

        $user = User::findOrFail($userId);
        $gajiPokok = $user->gaji_pokok ?? 0;

        // 1. Hitung Nilai Harian Standar (Prorate)
        $nilaiHarian = $gajiPokok / self::DIVIDER_HARI_KERJA;

        // 2. Ambil data Absensi dalam periode
        $absensis = Absensi::where('user_id', $userId)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->with('shift')
            ->get();

        $scheduledShifts = UserShift::where('user_id', $userId)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereDate('tanggal', '<', now()->toDateString())
            ->get();

        $absensisBySchedule = $absensis->keyBy(function ($absensi) {
            return $absensi->tanggal.'-'.($absensi->shift_id ?? 'none');
        });

        $approvedAttendanceDates = $this->approvedAttendanceDates($userId, $startDate, $endDate);

        // Alpha dihitung dari jadwal shift lampau yang kosong/status alpha,
        // plus record absensi yang memang disimpan sebagai alpha.
        $alphaScheduleKeys = $scheduledShifts->filter(function ($userShift) use ($absensisBySchedule, $approvedAttendanceDates) {
            if ($approvedAttendanceDates->contains($userShift->tanggal->toDateString())) {
                return false;
            }

            $key = $userShift->tanggal->toDateString().'-'.$userShift->shift_id;
            $absensi = $absensisBySchedule->get($key);

            return ! $absensi || $absensi->status === 'alpha';
        })->map(function ($userShift) {
            return $userShift->tanggal->toDateString().'-'.$userShift->shift_id;
        });

        $explicitAlphaKeys = $absensis
            ->where('status', 'alpha')
            ->filter(function ($absensi) use ($approvedAttendanceDates) {
                return Carbon::parse($absensi->tanggal)->lt(now()->startOfDay())
                    && ! $approvedAttendanceDates->contains(Carbon::parse($absensi->tanggal)->toDateString());
            })
            ->map(function ($absensi) {
                return $absensi->tanggal.'-'.($absensi->shift_id ?? 'none');
            });

        // Jangan biarkan bulan lanjutan dalam periode cut-off hilang dari
        // perhitungan hanya karena seluruh jadwal bulan itu belum dibuat.
        $unscheduledAlphaKeys = $this->unscheduledAlphaDates(
            $userId,
            $startDate,
            $endDate,
            $absensis,
            $scheduledShifts,
            $approvedAttendanceDates
        )->map(fn ($date) => $date.'-unscheduled');

        $countAlpha = collect($alphaScheduleKeys->all())
            ->concat($explicitAlphaKeys->all())
            ->concat($unscheduledAlphaKeys->all())
            ->unique()
            ->count();

        $countTerlambat = $absensis->where('status', 'terlambat')->count();

        // 3. Ambil data Double Shift dari UserShift.
        // Batasi sampai hari ini dan abaikan flag lama yang menempel pada shift non-double.
        $countDoubleShift = UserShift::where('user_id', $userId)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereDate('tanggal', '<=', now()->toDateString())
            ->where('is_double_shift', true)
            ->whereHas('shift', function ($query) {
                $query->where('nama_shift', 'like', '%double%');
            })
            ->count();

        $countTidakClockOut = $absensis->where('status', 'tidak clock out')->count();

        // 4. Kalkulasi Komponen
        $totalDoubleShiftBonus = $countDoubleShift * $nilaiHarian;
        $totalPotonganAlpha = $countAlpha * $nilaiHarian;
        $totalDendaTerlambat = $absensis->where('status', 'terlambat')->sum(function ($abs) {
            return $abs->shift->denda_telat ?? self::DENDA_TERLAMBAT;
        });

        $totalPotonganTidakClockOut = 0;
        foreach ($absensis->where('status', 'tidak clock out') as $abs) {
            $denda = $abs->denda_missing_clockout;
            if (($denda === null || $denda == 0) && $abs->shift) {
                $denda = $abs->shift->denda_missing_clockout;
            }
            $totalPotonganTidakClockOut += $denda;
        }

        // 5. Formula Akhir, dengan batas minimum Rp0 agar payroll tidak minus.
        $gajiDiterima = max(
            0,
            $gajiPokok
                + $totalDoubleShiftBonus
                - $totalPotonganAlpha
                - $totalDendaTerlambat
                - $totalPotonganTidakClockOut
        );

        return [
            'user_id' => $userId,
            'user_name' => $user->name,
            'periode' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'komponen' => [
                'gaji_pokok' => $gajiPokok,
                'nilai_harian' => $nilaiHarian,
                'count_alpha' => $countAlpha,
                'count_terlambat' => $countTerlambat,
                'count_double_shift' => $countDoubleShift,
                'count_tidak_clock_out' => $countTidakClockOut,
            ],
            'kalkulasi' => [
                'bonus_double_shift' => $totalDoubleShiftBonus,
                'potongan_alpha' => $totalPotonganAlpha,
                'denda_terlambat' => $totalDendaTerlambat,
                'potongan_tidak_clock_out' => $totalPotonganTidakClockOut,
                'gaji_diterima' => $gajiDiterima,
            ],
        ];
    }

    /**
     * Hitung gaji bulanan massal/tunggal dan simpan ke database.
     *
     * @param  int  $userId
     * @param  int  $year
     * @param  int  $month
     * @return \App\Models\Payroll
     *
     * @throws \Exception
     */
    public function hitungGajiBulanan($userId, $year, $month)
    {
        try {
            [, $endDate] = $this->getCutoffPeriod((int) $year, (int) $month);

            $calc = $this->calculate($userId, $endDate);

            return \App\Models\Payroll::updateOrCreate(
                [
                    'user_id' => $userId,
                    'periode_mulai' => $calc['periode']['start'],
                    'periode_selesai' => $calc['periode']['end'],
                ],
                [
                    'gaji_pokok' => $calc['komponen']['gaji_pokok'],
                    'insentif_double_shift' => $calc['kalkulasi']['bonus_double_shift'],
                    'potongan_alpha' => $calc['kalkulasi']['potongan_alpha'],
                    'potongan_telat' => $calc['kalkulasi']['denda_terlambat'],
                    'potongan_tidak_clock_out' => $calc['kalkulasi']['potongan_tidak_clock_out'],
                    'gaji_bersih' => $calc['kalkulasi']['gaji_diterima'],
                ]
            );
        } catch (\Exception $e) {
            \Log::error("Gagal menghitung gaji bulanan untuk User ID {$userId}: ".$e->getMessage());
            throw $e;
        }
    }

    public function recalculateForDate($userId, $date)
    {
        $date = Carbon::parse($date);
        $periodStart = $date->day >= 26
            ? $date->copy()->startOfMonth()
            : $date->copy()->subMonthNoOverflow()->startOfMonth();

        return $this->hitungGajiBulanan($userId, $periodStart->year, $periodStart->month);
    }

    public function approvedAttendanceDates($userId, Carbon $startDate, Carbon $endDate)
    {
        $dates = AttendanceCorrection::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->pluck('tanggal')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        IzinAbsensi::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereDate('tanggal_mulai', '<=', $endDate->toDateString())
            ->whereDate('tanggal_selesai', '>=', $startDate->toDateString())
            ->get()
            ->each(function ($leave) use ($dates, $startDate, $endDate) {
                $rangeStart = Carbon::parse($leave->tanggal_mulai)->max($startDate);
                $rangeEnd = Carbon::parse($leave->tanggal_selesai)->min($endDate);

                for ($date = $rangeStart->copy(); $date->lte($rangeEnd); $date->addDay()) {
                    $dates->push($date->toDateString());
                }
            });

        return $dates->unique()->values();
    }

    public function unscheduledAlphaDates(
        $userId,
        Carbon $startDate,
        Carbon $endDate,
        $absensis = null,
        $scheduledShifts = null,
        $approvedAttendanceDates = null
    ) {
        $lastEligibleDate = $endDate->copy()->min(now()->subDay())->startOfDay();

        if ($lastEligibleDate->lt($startDate->copy()->startOfDay())) {
            return collect();
        }

        $absensis ??= Absensi::where('user_id', $userId)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $scheduledShifts ??= UserShift::where('user_id', $userId)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $approvedAttendanceDates ??= $this->approvedAttendanceDates($userId, $startDate, $endDate);

        $attendanceDates = $absensis
            ->map(fn ($absensi) => Carbon::parse($absensi->tanggal)->toDateString())
            ->unique();

        $scheduledDates = $scheduledShifts
            ->map(fn ($userShift) => Carbon::parse($userShift->tanggal)->toDateString())
            ->unique();

        $scheduledMonths = $scheduledDates
            ->groupBy(fn ($date) => Carbon::parse($date)->format('Y-m'));

        $dates = collect();

        for ($date = $startDate->copy()->startOfDay(); $date->lte($lastEligibleDate); $date->addDay()) {
            $dateString = $date->toDateString();
            $monthKey = $date->format('Y-m');
            $hasEarlierSchedule = $scheduledDates->contains(
                fn ($scheduledDate) => Carbon::parse($scheduledDate)->lt($date)
            );

            if (
                ! $attendanceDates->contains($dateString)
                && ! $scheduledDates->contains($dateString)
                && ! $approvedAttendanceDates->contains($dateString)
                && ! $scheduledMonths->has($monthKey)
                && $hasEarlierSchedule
            ) {
                $dates->push($dateString);
            }
        }

        return $dates;
    }
}
