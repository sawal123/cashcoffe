<?php

namespace App\Livewire\Payroll;

use App\Models\User;
use App\Models\Payroll;
use App\Services\PayrollService;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class GenerasiGaji extends Component
{
    public $month;
    public $year;

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

    public function render()
    {
        $endDate = Carbon::createFromDate((int)$this->year, (int)$this->month, 25)->toDateString();
        $startDate = Carbon::createFromDate((int)$this->year, (int)$this->month, 25)->subMonth()->day(26)->toDateString();

        $payrolls = Payroll::with(['user.jabatan'])
            ->where('periode_mulai', $startDate)
            ->where('periode_selesai', $endDate)
            ->get();

        return view('livewire.payroll.generasi-gaji', [
            'payrolls' => $payrolls,
            'startDateFormatted' => Carbon::parse($startDate)->format('d M Y'),
            'endDateFormatted' => Carbon::parse($endDate)->format('d M Y'),
        ])->layout('layouts.app', ['title' => 'Generasi Gaji Bulanan']);
    }
}
