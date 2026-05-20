<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shift;
use App\Models\Absensi;
use App\Models\UserShift;
use App\Models\Jabatan;
use App\Services\PayrollService;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoClockOutAndPayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_clock_out_command_detects_and_updates_missing_clock_out()
    {
        // Setup role
        Role::create(['name' => 'karyawan', 'guard_name' => 'web']);

        // 1. Create a Shift with custom missing clockout denda
        $shift = Shift::create([
            'nama_shift' => 'Pagi Test',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
            'denda_telat' => 10000.00,
            'maksimal_telat_menit' => 30,
            'batas_awal_absen_menit' => 60,
            'denda_missing_clockout' => 25000.00,
        ]);

        // 2. Create a User with employee role (since query in command filters by role 'karyawan')
        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Staff Test',
            'gapok' => 4000000.00,
            'tunjangan_jabatan' => 500000.00,
        ]);

        $user = User::factory()->create([
            'jabatan_id' => $jabatan->id,
            'name' => 'Karyawan Test',
        ]);
        $user->assignRole('karyawan');

        // Assign user to shift for today
        UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'tanggal' => Carbon::today()->toDateString(),
        ]);

        // 3. Create a missing clock-out attendance (jam_masuk set, jam_keluar NULL)
        $absen = Absensi::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'tanggal' => Carbon::today()->toDateString(),
            'jam_masuk' => '08:00:00',
            'jam_keluar' => null,
            'status' => 'hadir',
        ]);

        // 4. Run the Artisan command
        $this->artisan('attendance:auto-clock-out')
            ->assertExitCode(0);

        // 5. Assert database state updated correctly
        $absen->refresh();
        $this->assertEquals('tidak clock out', $absen->status);
        $this->assertEquals(25000.00, $absen->denda_missing_clockout);
    }

    public function test_payroll_calculates_and_subtracts_tidak_clock_out_deduction()
    {
        // Setup role
        Role::create(['name' => 'karyawan', 'guard_name' => 'web']);

        // 1. Create a Shift with missing clockout denda
        $shift = Shift::create([
            'nama_shift' => 'Pagi Test 2',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
            'denda_telat' => 10000.00,
            'maksimal_telat_menit' => 30,
            'batas_awal_absen_menit' => 60,
            'denda_missing_clockout' => 35000.00,
        ]);

        // 2. Create a User with salary information
        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Staff Test 2',
            'gapok' => 3000000.00,
            'tunjangan_jabatan' => 200000.00,
        ]);

        $user = User::factory()->create([
            'jabatan_id' => $jabatan->id,
            'name' => 'Karyawan Test 2',
            'gaji_pokok' => 3000000.00,
        ]);
        $user->assignRole('karyawan');

        // Assign user to shift
        UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'tanggal' => Carbon::now()->day(15)->toDateString(),
        ]);

        // 3. Create a 'tidak clock out' attendance record
        Absensi::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'tanggal' => Carbon::now()->day(15)->toDateString(),
            'jam_masuk' => '08:00:00',
            'jam_keluar' => null,
            'status' => 'tidak clock out',
            'denda_missing_clockout' => 35000.00,
        ]);

        // 4. Calculate payroll via service
        $payrollService = new PayrollService();
        $payrollData = $payrollService->calculate($user->id, Carbon::now()->day(25));

        // 5. Assert the deductions and final salary
        $this->assertEquals(35000.00, $payrollData['kalkulasi']['potongan_tidak_clock_out']);
        
        // Total salary = gross - total deductions
        $this->assertEquals(3000000 - 35000, $payrollData['kalkulasi']['gaji_diterima']);
    }
}
