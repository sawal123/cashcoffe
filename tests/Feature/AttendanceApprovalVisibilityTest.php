<?php

namespace Tests\Feature;

use App\Livewire\Absense\ManageRequest;
use App\Livewire\Absense\ShowAbsense;
use App\Models\Absensi;
use App\Models\AttendanceCorrection;
use App\Models\IzinAbsensi;
use App\Models\Payroll;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserShift;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceApprovalVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_correction_is_linked_and_visible_in_employee_attendance(): void
    {
        $admin = User::factory()->create();
        $employee = User::factory()->create(['gaji_pokok' => 2500000]);
        $date = now()->startOfMonth()->toDateString();

        $shift = Shift::create([
            'nama_shift' => 'Shift Pagi',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
            'maksimal_telat_menit' => 60,
            'denda_telat' => 20000,
        ]);

        UserShift::create([
            'user_id' => $employee->id,
            'shift_id' => $shift->id,
            'tanggal' => $date,
        ]);

        $correction = AttendanceCorrection::create([
            'user_id' => $employee->id,
            'tanggal' => $date,
            'jam_masuk_baru' => '08:15',
            'jam_keluar_baru' => '17:00',
            'alasan' => 'Perangkat absensi bermasalah saat datang.',
            'status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->test(ManageRequest::class)
            ->set('type', 'correction')
            ->set('jenis', 'all')
            ->call('approveCorrection', $correction->id)
            ->assertDispatched('showToast')
            ->assertSee('Lihat Absensi');

        $attendance = Absensi::where('user_id', $employee->id)
            ->whereDate('tanggal', $date)
            ->firstOrFail();

        $this->assertDatabaseHas('attendance_corrections', [
            'id' => $correction->id,
            'absensi_id' => $attendance->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);

        $this->assertSame('terlambat', $attendance->status);
        $this->assertStringContainsString('Diperbaiki melalui persetujuan', $attendance->keterangan);

        $payroll = Payroll::where('user_id', $employee->id)->firstOrFail();
        $this->assertEquals(0, $payroll->potongan_alpha);
        $this->assertEquals(20000, $payroll->potongan_telat);

        Livewire::actingAs($admin)
            ->test(ShowAbsense::class, ['userId' => $employee->id])
            ->set('month', now()->month)
            ->set('year', now()->year)
            ->assertSee('Absensi diperbaiki')
            ->assertSee('Perangkat absensi bermasalah saat datang.')
            ->assertSee($admin->name);
    }

    public function test_approved_leave_is_visible_in_employee_attendance(): void
    {
        $admin = User::factory()->create();
        $employee = User::factory()->create([
            'jatah_cuti' => 10,
            'gaji_pokok' => 2500000,
        ]);
        $date = now()->startOfMonth()->toDateString();

        $leave = IzinAbsensi::create([
            'user_id' => $employee->id,
            'tanggal_mulai' => $date,
            'tanggal_selesai' => $date,
            'jenis' => 'cuti',
            'alasan' => 'Acara keluarga yang sudah dijadwalkan.',
            'status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->test(ManageRequest::class)
            ->call('approveLeave', $leave->id)
            ->assertDispatched('showToast')
            ->assertSee('Lihat Absensi');

        $this->assertDatabaseHas('absensis', [
            'user_id' => $employee->id,
            'tanggal' => $date,
            'status' => 'cuti',
            'keterangan' => 'Cuti disetujui: Acara keluarga yang sudah dijadwalkan.',
        ]);

        $payroll = Payroll::where('user_id', $employee->id)->firstOrFail();
        $this->assertEquals(0, $payroll->potongan_alpha);

        Livewire::actingAs($admin)
            ->test(ShowAbsense::class, ['userId' => $employee->id])
            ->set('month', now()->month)
            ->set('year', now()->year)
            ->assertSee('Cuti disetujui')
            ->assertSee('Acara keluarga yang sudah dijadwalkan.')
            ->assertSee($admin->name);
    }

    public function test_legacy_approved_correction_without_shift_is_not_counted_as_alpha(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 23, 12));

        try {
            $employee = User::factory()->create(['gaji_pokok' => 1800000]);
            $shift = Shift::create([
                'nama_shift' => 'Pagi',
                'jam_masuk' => '10:00:00',
                'jam_keluar' => '19:00:00',
                'maksimal_telat_menit' => 15,
                'denda_telat' => 20000,
            ]);

            UserShift::create([
                'user_id' => $employee->id,
                'shift_id' => $shift->id,
                'tanggal' => '2026-06-02',
            ]);

            Absensi::create([
                'user_id' => $employee->id,
                'tanggal' => '2026-06-02',
                'shift_id' => null,
                'jam_masuk' => '09:59:00',
                'jam_keluar' => '17:00:00',
                'status' => 'hadir',
            ]);

            AttendanceCorrection::create([
                'user_id' => $employee->id,
                'tanggal' => '2026-06-02',
                'jam_masuk_baru' => '09:59',
                'jam_keluar_baru' => '17:00',
                'alasan' => 'HP tertinggal.',
                'status' => 'approved',
            ]);

            $calculation = (new PayrollService())->calculate(
                $employee->id,
                Carbon::create(2026, 6, 25)
            );

            $this->assertSame(0, $calculation['komponen']['count_alpha']);
            $this->assertEquals(0, $calculation['kalkulasi']['potongan_alpha']);
        } finally {
            Carbon::setTestNow();
        }
    }
}
