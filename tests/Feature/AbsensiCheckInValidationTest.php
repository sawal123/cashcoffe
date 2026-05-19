<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shift;
use App\Models\UserShift;
use App\Models\Branch;
use App\Livewire\Absensi\Verifikasi;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class AbsensiCheckInValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'web']);
    }

    public function test_cannot_check_in_more_than_one_hour_before_shift_starts()
    {
        $branch = Branch::create([
            'nama_cabang' => 'Cabang Test',
            'latitude' => '-6.200000',
            'longitude' => '106.816666',
            'radius_meter' => 200,
        ]);

        $user = User::factory()->create([
            'branch_id' => $branch->id,
        ]);
        $user->assignRole('karyawan');

        $shift = Shift::create([
            'nama_shift' => 'Shift Pagi',
            'jam_masuk' => '10:00:00',
            'jam_keluar' => '18:00:00',
            'maksimal_telat_menit' => 60,
            'denda_telat' => 20000,
        ]);

        // Set waktu sekarang ke 08:50 (terlalu awal, shift mulai 10:00, batas awal check-in 09:00)
        Carbon::setTestNow(Carbon::create(2026, 5, 19, 8, 50, 0, 'Asia/Jakarta'));

        $userShift = UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'tanggal' => '2026-05-19',
        ]);

        Livewire::actingAs($user)
            ->test(Verifikasi::class, ['type' => 'masuk'])
            ->set('fotoBase64', 'data:image/jpeg;base64,abcdef')
            ->set('lokasiStr', '-6.200000,106.816666')
            ->call('submitVerifikasi')
            ->assertRedirect('/absen/clock-in')
            ->assertSessionHas('error', 'Gagal melakukan absensi. Anda hanya diperbolehkan absen masuk maksimal 1 jam sebelum shift dimulai.');

        // Clean up Carbon::setTestNow
        Carbon::setTestNow();
    }

    public function test_can_check_in_within_one_hour_before_shift_starts()
    {
        $branch = Branch::create([
            'nama_cabang' => 'Cabang Test',
            'latitude' => '-6.200000',
            'longitude' => '106.816666',
            'radius_meter' => 200,
        ]);

        $user = User::factory()->create([
            'branch_id' => $branch->id,
        ]);
        $user->assignRole('karyawan');

        $shift = Shift::create([
            'nama_shift' => 'Shift Pagi',
            'jam_masuk' => '10:00:00',
            'jam_keluar' => '18:00:00',
            'maksimal_telat_menit' => 60,
            'denda_telat' => 20000,
        ]);

        // Set waktu sekarang ke 09:15 (di dalam rentang 1 jam sebelum shift)
        Carbon::setTestNow(Carbon::create(2026, 5, 19, 9, 15, 0, 'Asia/Jakarta'));

        $userShift = UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'tanggal' => '2026-05-19',
        ]);

        Livewire::actingAs($user)
            ->test(Verifikasi::class, ['type' => 'masuk'])
            ->set('fotoBase64', 'data:image/jpeg;base64,abcdef')
            ->set('lokasiStr', '-6.200000,106.816666')
            ->call('submitVerifikasi')
            ->assertRedirect('/absen')
            ->assertSessionHas('success');

        // Clean up Carbon::setTestNow
        Carbon::setTestNow();
    }

    public function test_cannot_check_in_more_than_custom_limit_before_shift_starts()
    {
        $branch = Branch::create([
            'nama_cabang' => 'Cabang Test',
            'latitude' => '-6.200000',
            'longitude' => '106.816666',
            'radius' => 200,
        ]);

        $user = User::factory()->create([
            'branch_id' => $branch->id,
        ]);
        $user->assignRole('karyawan');

        $shift = Shift::create([
            'nama_shift' => 'Shift Sore',
            'jam_masuk' => '15:00:00',
            'jam_keluar' => '23:00:00',
            'maksimal_telat_menit' => 60,
            'denda_telat' => 20000,
            'batas_awal_absen_menit' => 30,
        ]);

        // Set waktu sekarang ke 14:25 (35 menit sebelum shift, ditolak karena batas awal absen 14:30)
        Carbon::setTestNow(Carbon::create(2026, 5, 19, 14, 25, 0, 'Asia/Jakarta'));

        $userShift = UserShift::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'tanggal' => '2026-05-19',
        ]);

        Livewire::actingAs($user)
            ->test(Verifikasi::class, ['type' => 'masuk'])
            ->set('fotoBase64', 'data:image/jpeg;base64,abcdef')
            ->set('lokasiStr', '-6.200000,106.816666')
            ->call('submitVerifikasi')
            ->assertRedirect('/absen/clock-in')
            ->assertSessionHas('error', 'Gagal melakukan absensi. Anda hanya diperbolehkan absen masuk maksimal 30 menit sebelum shift dimulai.');

        // Clean up Carbon::setTestNow
        Carbon::setTestNow();
    }
}
