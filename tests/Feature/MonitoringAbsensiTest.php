<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shift;
use App\Models\Absensi;
use App\Models\UserShift;
use App\Livewire\Absense\TableAbsense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class MonitoringAbsensiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'web']);
    }

    public function test_can_manually_add_attendance_for_employee_today()
    {
        $user = User::factory()->create();
        $user->assignRole('karyawan');

        $shift = Shift::create([
            'nama_shift' => 'Shift Pagi',
            'jam_masuk' => '10:00:00',
            'jam_keluar' => '18:00:00',
            'maksimal_telat_menit' => 60,
            'denda_telat' => 20000,
        ]);

        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(TableAbsense::class)
            ->call('openTambahAbsen', $user->id)
            ->assertSet('userIdForAbsen', $user->id)
            ->assertSet('selectedUserName', $user->name)
            ->set('status', 'hadir')
            ->set('jamMasuk', '10:00')
            ->set('jamKeluar', '18:00')
            ->set('keterangan', 'Hadir manual')
            ->call('storeManualAbsen')
            ->assertDispatched('close-modal', name: 'modal-tambah-absen')
            ->assertDispatched('showToast');

        $this->assertDatabaseHas('absensis', [
            'user_id' => $user->id,
            'status' => 'hadir',
            'jam_masuk' => '10:00',
            'jam_keluar' => '18:00',
            'keterangan' => 'Hadir manual',
            'tanggal' => now()->toDateString(),
        ]);
    }

    public function test_can_manually_update_and_delete_attendance_for_employee_today()
    {
        $user = User::factory()->create();
        $user->assignRole('karyawan');

        $shift = Shift::create([
            'nama_shift' => 'Shift Pagi',
            'jam_masuk' => '10:00:00',
            'jam_keluar' => '18:00:00',
            'maksimal_telat_menit' => 60,
            'denda_telat' => 20000,
        ]);

        $absen = Absensi::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_masuk' => '10:00:00',
        ]);

        $admin = User::factory()->create();

        // 1. Test Load and Update Detail
        Livewire::actingAs($admin)
            ->test(TableAbsense::class)
            ->call('openDetailHariIni', $absen->id)
            ->assertSet('absensiId', $absen->id)
            ->assertSet('status', 'hadir')
            ->assertHasNoErrors()
            ->set('status', 'terlambat')
            ->set('keterangan', 'Terlambat sedikit')
            ->call('updateManualAbsen')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', name: 'modal-detail-absen')
            ->assertDispatched('showToast');

        $this->assertDatabaseHas('absensis', [
            'id' => $absen->id,
            'status' => 'terlambat',
            'keterangan' => 'Terlambat sedikit',
        ]);

        // 2. Test Delete
        Livewire::actingAs($admin)
            ->test(TableAbsense::class)
            ->call('openDetailHariIni', $absen->id)
            ->assertHasNoErrors()
            ->call('deleteManualAbsen')
            ->assertHasNoErrors()
            ->assertDispatched('close-modal', name: 'modal-detail-absen')
            ->assertDispatched('showToast');

        $this->assertDatabaseMissing('absensis', [
            'id' => $absen->id,
        ]);
    }

    public function test_can_view_read_only_detail_modal_for_employee_today()
    {
        $user = User::factory()->create();
        $user->assignRole('karyawan');

        $shift = Shift::create([
            'nama_shift' => 'Shift Sore',
            'jam_masuk' => '14:00:00',
            'jam_keluar' => '22:00:00',
            'maksimal_telat_menit' => 60,
            'denda_telat' => 20000,
        ]);

        $absen = Absensi::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'tanggal' => now()->toDateString(),
            'status' => 'hadir',
            'jam_masuk' => '14:05:00',
        ]);

        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(TableAbsense::class)
            ->assertSet('showDetailModal', false)
            ->assertSet('selectedAbsenDetail', null)
            ->call('showDetail', $absen->id)
            ->assertSet('showDetailModal', true)
            ->assertSet('selectedAbsenDetail.id', $absen->id)
            ->call('closeDetailModal')
            ->assertSet('showDetailModal', false)
            ->assertSet('selectedAbsenDetail', null);
    }
}
