<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\IzinAbsensi;
use App\Livewire\Absense\RequestLeave;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class RequestLeaveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'web']);
    }

    public function test_employee_can_open_form_modal()
    {
        $user = User::factory()->create();
        $user->assignRole('karyawan');

        Livewire::actingAs($user)
            ->test(RequestLeave::class)
            ->assertSet('showFormModal', false)
            ->call('createRequest')
            ->assertSet('showFormModal', true)
            ->assertSet('isEditMode', false);
    }

    public function test_employee_can_submit_izin()
    {
        $user = User::factory()->create();
        $user->assignRole('karyawan');

        Livewire::actingAs($user)
            ->test(RequestLeave::class)
            ->call('createRequest')
            ->set('tanggal_mulai', now()->addDay()->format('Y-m-d'))
            ->set('tanggal_selesai', now()->addDay()->format('Y-m-d'))
            ->set('jenis', 'izin')
            ->set('alasan', 'Keperluan keluarga yang tidak bisa ditinggalkan.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false)
            ->assertDispatched('showToast');

        $this->assertDatabaseHas('izin_absensis', [
            'user_id' => $user->id,
            'jenis' => 'izin',
            'alasan' => 'Keperluan keluarga yang tidak bisa ditinggalkan.',
            'status' => 'pending',
        ]);
    }

    public function test_employee_can_submit_cuti_with_sufficient_quota()
    {
        $user = User::factory()->create(['jatah_cuti' => 10]);
        $user->assignRole('karyawan');

        Livewire::actingAs($user)
            ->test(RequestLeave::class)
            ->call('createRequest')
            ->set('tanggal_mulai', now()->addDay()->format('Y-m-d'))
            ->set('tanggal_selesai', now()->addDays(3)->format('Y-m-d'))
            ->set('jenis', 'cuti')
            ->set('alasan', 'Liburan bersama keluarga ke luar kota.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false);

        $this->assertDatabaseHas('izin_absensis', [
            'user_id' => $user->id,
            'jenis' => 'cuti',
            'status' => 'pending',
        ]);
    }

    public function test_employee_can_open_detail_modal()
    {
        $user = User::factory()->create();
        $user->assignRole('karyawan');

        $req = IzinAbsensi::create([
            'user_id' => $user->id,
            'tanggal_mulai' => now()->addDay()->format('Y-m-d'),
            'tanggal_selesai' => now()->addDay()->format('Y-m-d'),
            'jenis' => 'izin',
            'alasan' => 'Keperluan mendadak tidak bisa ditinggalkan.',
            'status' => 'pending',
        ]);

        Livewire::actingAs($user)
            ->test(RequestLeave::class)
            ->assertSet('showDetailModal', false)
            ->call('viewRequest', $req->id)
            ->assertSet('showDetailModal', true)
            ->assertSet('viewingRequest.id', $req->id);
    }
}
