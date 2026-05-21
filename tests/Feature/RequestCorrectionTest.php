<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shift;
use App\Models\Absensi;
use App\Models\AttendanceCorrection;
use App\Livewire\Absense\RequestCorrection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class RequestCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'web']);
    }

    public function test_employee_can_open_modal()
    {
        $user = User::factory()->create();
        $user->assignRole('karyawan');

        Livewire::actingAs($user)
            ->test(RequestCorrection::class)
            ->assertSet('showFormModal', false)
            ->call('createCorrection')
            ->assertSet('showFormModal', true)
            ->assertSet('isEditMode', false);
    }

    public function test_employee_can_submit_correction()
    {
        $user = User::factory()->create();
        $user->assignRole('karyawan');

        Livewire::actingAs($user)
            ->test(RequestCorrection::class)
            ->call('createCorrection')
            ->set('tanggal', now()->format('Y-m-d'))
            ->set('jam_masuk_baru', '08:00')
            ->set('jam_keluar_baru', '17:00')
            ->set('alasan', 'Lupa absen masuk dan keluar karena terburu-buru.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched('showToast');

        $this->assertDatabaseHas('attendance_corrections', [
            'user_id' => $user->id,
            'tanggal' => now()->format('Y-m-d'),
            'jam_masuk_baru' => '08:00',
            'jam_keluar_baru' => '17:00',
            'alasan' => 'Lupa absen masuk dan keluar karena terburu-buru.',
            'status' => 'pending',
        ]);
    }

    public function test_employee_can_edit_correction_with_seconds_from_db()
    {
        $user = User::factory()->create();
        $user->assignRole('karyawan');

        // Pre-create a correction with seconds in DB format (HH:MM:SS)
        $cor = AttendanceCorrection::create([
            'user_id' => $user->id,
            'tanggal' => now()->format('Y-m-d'),
            'jam_masuk_baru' => '08:00:00',
            'jam_keluar_baru' => '17:00:00',
            'alasan' => 'Lupa absen masuk dan keluar karena terburu-buru.',
            'status' => 'pending',
        ]);

        // editCorrection strips seconds from time (Carbon::parse -> H:i), so '08:00:00' becomes '08:00'
        Livewire::actingAs($user)
            ->test(RequestCorrection::class)
            ->call('editCorrection', $cor->id)
            ->assertSet('jam_masuk_baru', '08:00')
            ->assertSet('jam_keluar_baru', '17:00')
            ->assertSet('isEditMode', true)
            ->assertSet('showFormModal', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false);
    }
}
