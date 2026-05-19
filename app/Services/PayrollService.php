<?php

namespace App\Services;

use App\Models\User;
use App\Models\Absensi;
use App\Models\UserShift;
use Carbon\Carbon;

class PayrollService
{
    /**
     * Tetapkan konstanta pembagi gaji prorate sesuai permintaan user.
     */
    const DIVIDER_HARI_KERJA = 25;
    const DENDA_TERLAMBAT = 20000;

    /**
     * Menghitung kalkulasi payroll bulanan per user_id.
     * Periode: Tanggal 26 bulan sebelumnya s/d Tanggal 25 bulan berjalan.
     *
     * @param int $userId
     * @param Carbon|null $endDate (Default: Tanggal 25 bulan berjalan)
     * @return array
     */
    public function calculate($userId, Carbon $endDate = null)
    {
        if (!$endDate) {
            $endDate = Carbon::now()->day(25)->endOfDay();
        } else {
            $endDate = $endDate->copy()->day(25)->endOfDay();
        }

        $startDate = $endDate->copy()->subMonth()->day(26)->startOfDay();

        $user = User::findOrFail($userId);
        $gajiPokok = $user->gaji_pokok ?? 0;

        // 1. Hitung Nilai Harian Standar (Prorate)
        $nilaiHarian = $gajiPokok / self::DIVIDER_HARI_KERJA;

        // 2. Ambil data Absensi dalam periode
        $absensis = Absensi::where('user_id', $userId)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $countAlpha = $absensis->where('status', 'alpha')->count();
        $countTerlambat = $absensis->where('status', 'terlambat')->count();

        // 3. Ambil data Double Shift dari UserShift
        // Sesuai request: jumlah record user_shifts dengan is_double_shift = true
        $countDoubleShift = UserShift::where('user_id', $userId)
            ->whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('is_double_shift', true)
            ->count();

        // 4. Kalkulasi Komponen
        $totalDoubleShiftBonus = $countDoubleShift * $nilaiHarian;
        $totalPotonganAlpha = $countAlpha * $nilaiHarian;
        $totalDendaTerlambat = $countTerlambat * self::DENDA_TERLAMBAT;

        // 5. Formula Akhir:
        // Gaji_Diterima = Gaji_Pokok_Bulanan + (Total_Double_Shift * Nilai_Harian) - Total_Potongan_Alpha - Total_Denda_Keterlambatan
        $gajiDiterima = $gajiPokok + $totalDoubleShiftBonus - $totalPotonganAlpha - $totalDendaTerlambat;

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
            ],
            'kalkulasi' => [
                'bonus_double_shift' => $totalDoubleShiftBonus,
                'potongan_alpha' => $totalPotonganAlpha,
                'denda_terlambat' => $totalDendaTerlambat,
                'gaji_diterima' => $gajiDiterima,
            ]
        ];
    }
}
