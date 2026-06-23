<?php

namespace Tests\Feature;

use App\Livewire\Absensi\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'web']);
    }

    public function test_employee_can_update_name_and_profile_photo_but_not_email(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'pegawai@example.com',
        ]);
        $user->assignRole('karyawan');

        $photo = UploadedFile::fake()->image('profil.jpg', 500, 500);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->call('openEditProfile')
            ->assertSet('showEditProfileModal', true)
            ->set('name', 'Nama Baru')
            ->set('avatar', $photo)
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertSet('showEditProfileModal', false)
            ->assertDispatched('showToast');

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('pegawai@example.com', $user->email);
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }
}
