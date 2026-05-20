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

        // Populate other days as present to avoid alpha deduction
        $endDateVal = Carbon::now()->day(25)->endOfDay();
        $startDateVal = $endDateVal->copy()->subMonth()->day(26)->startOfDay();
        $targetDate = Carbon::now()->day(15)->toDateString();

        $tempDate = $startDateVal->copy()->startOfDay();
        while ($tempDate->lte($endDateVal)) {
            if ($tempDate->isPast() && $tempDate->toDateString() !== $targetDate) {
                Absensi::create([
                    'user_id' => $user->id,
                    'shift_id' => $shift->id,
                    'tanggal' => $tempDate->toDateString(),
                    'status' => 'hadir',
                    'jam_masuk' => '08:00:00',
                    'jam_keluar' => '17:00:00',
                ]);
            }
            $tempDate->addDay();
        }

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

    public function test_payroll_service_can_calculate_and_save_monthly_payroll()
    {
        // Setup role
        Role::create(['name' => 'karyawan', 'guard_name' => 'web']);

        $shift = Shift::create([
            'nama_shift' => 'Pagi Test 3',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
            'denda_telat' => 10000.00,
            'maksimal_telat_menit' => 30,
            'batas_awal_absen_menit' => 60,
            'denda_missing_clockout' => 20000.00,
        ]);

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Staff Test 3',
            'gapok' => 2500000.00,
            'tunjangan_jabatan' => 100000.00,
        ]);

        $user = User::factory()->create([
            'jabatan_id' => $jabatan->id,
            'name' => 'Karyawan Test 3',
            'gaji_pokok' => 2500000.00,
        ]);
        $user->assignRole('karyawan');

        // Let's test for May 2026. The range is 26 April 2026 to 25 May 2026.
        // Let's create an attendance on 5 May 2026.
        UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'tanggal' => '2026-05-05',
        ]);

        // Populate other days in May 2026 range as present to avoid alpha deduction
        $startDateVal2 = Carbon::createFromDate(2026, 5, 25)->subMonth()->day(26)->startOfDay();
        $endDateVal2 = Carbon::createFromDate(2026, 5, 25)->endOfDay();
        $targetDate2 = '2026-05-05';

        $tempDate2 = $startDateVal2->copy()->startOfDay();
        while ($tempDate2->lte($endDateVal2)) {
            if ($tempDate2->toDateString() !== $targetDate2) {
                Absensi::create([
                    'user_id' => $user->id,
                    'shift_id' => $shift->id,
                    'tanggal' => $tempDate2->toDateString(),
                    'status' => 'hadir',
                    'jam_masuk' => '08:00:00',
                    'jam_keluar' => '17:00:00',
                ]);
            }
            $tempDate2->addDay();
        }

        Absensi::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'tanggal' => '2026-05-05',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => null,
            'status' => 'tidak clock out',
            'denda_missing_clockout' => 20000.00,
        ]);

        $payrollService = new PayrollService();
        $payroll = $payrollService->hitungGajiBulanan($user->id, 2026, 5);

        // Assert record is returned and populated
        $this->assertNotNull($payroll);
        $this->assertEquals($user->id, $payroll->user_id);
        $this->assertEquals('2026-04-26', $payroll->periode_mulai->toDateString());
        $this->assertEquals('2026-05-25', $payroll->periode_selesai->toDateString());
        $this->assertEquals(2500000.00, $payroll->gaji_pokok);
        $this->assertEquals(20000.00, $payroll->potongan_tidak_clock_out);
        $this->assertEquals(2500000.00 - 20000.00, $payroll->gaji_bersih);

        // Assert database record exists
        $this->assertDatabaseHas('payrolls', [
            'user_id' => $user->id,
            'periode_mulai' => '2026-04-26 00:00:00',
            'periode_selesai' => '2026-05-25 00:00:00',
            'gaji_bersih' => 2480000.00,
        ]);
    }
}
