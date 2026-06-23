<?php

namespace Tests\Feature;

use App\Livewire\Absense\RequestCorrection;
use App\Livewire\Absensi\History;
use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeAttendanceHistoryCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 6, 23, 12));
        Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'web']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_past_incomplete_attendance_shows_correction_actions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('karyawan');

        Absensi::create([
            'user_id' => $user->id,
            'tanggal' => '2026-06-20',
            'jam_masuk' => '14:01:00',
            'jam_keluar' => null,
            'status' => 'tidak clock out',
        ]);

        Absensi::create([
            'user_id' => $user->id,
            'tanggal' => '2026-06-19',
            'jam_masuk' => null,
            'jam_keluar' => '22:00:00',
            'status' => 'alpha',
        ]);

        Livewire::actingAs($user)
            ->test(History::class)
            ->assertSee('Perbaiki Clock In')
            ->assertSee('Perbaiki Clock Out')
            ->assertSeeHtml('field=clock_in')
            ->assertSeeHtml('field=clock_out');
    }

    public function test_correction_link_opens_prefilled_clock_out_form(): void
    {
        $user = User::factory()->create();
        $user->assignRole('karyawan');

        Absensi::create([
            'user_id' => $user->id,
            'tanggal' => '2026-06-20',
            'jam_masuk' => '14:01:00',
            'jam_keluar' => null,
            'status' => 'tidak clock out',
        ]);

        Livewire::actingAs($user);

        Livewire::withQueryParams([
            'tanggal' => '2026-06-20',
            'field' => 'clock_out',
        ])
            ->test(RequestCorrection::class)
            ->assertSet('showFormModal', true)
            ->assertSet('tanggal', '2026-06-20')
            ->assertSet('jam_masuk_baru', '14:01')
            ->assertSet('jam_keluar_baru', null)
            ->assertSet('alasan', 'Perbaikan clock out yang belum tercatat.');
    }
}
