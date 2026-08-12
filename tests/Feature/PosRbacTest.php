<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Menu;
use App\Models\Discount;
use App\Models\DiscountApproval;
use App\Models\Pesanan;
use App\Models\Pengeluaran;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;

class PosRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure roles exist
        Role::firstOrCreate(['name' => 'kasir']);
        Role::firstOrCreate(['name' => 'manager']);
        Role::firstOrCreate(['name' => 'superadmin']);
    }

    private function createKasir()
    {
        $user = User::factory()->create();
        $user->assignRole('kasir');
        return $user;
    }

    private function createManager()
    {
        $user = User::factory()->create();
        $user->assignRole('manager');
        return $user;
    }

    public function test_kasir_bisa_mengakses_order()
    {
        $kasir = $this->createKasir();
        
        $response = $this->actingAs($kasir)->get('/order');
        $response->assertStatus(200);
    }

    public function test_kasir_bisa_approve_diskon_general()
    {
        $kasir = $this->createKasir();
        $discount = Discount::factory()->create(['type' => 'general', 'nama_diskon' => 'General', 'jenis_diskon' => 'nominal', 'nilai_diskon' => 1000]);
        $approval = DiscountApproval::factory()->create([
            'discount_id' => $discount->id,
            'status' => 'pending'
        ]);

        Livewire::actingAs($kasir)
            ->test(\App\Livewire\Discount\ApprovalList::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('actionType', 'approve')
            ->set('keterangan', 'ok')
            ->call('submitAction')
            ->assertDispatched('close-modal');

        $this->assertEquals('approved', $approval->fresh()->status);
    }

    public function test_kasir_ditolak_approve_diskon_non_general()
    {
        $kasir = $this->createKasir();
        $discount = Discount::factory()->create(['type' => 'private', 'nama_diskon' => 'Private', 'jenis_diskon' => 'nominal', 'nilai_diskon' => 1000]);
        $approval = DiscountApproval::factory()->create([
            'discount_id' => $discount->id,
            'status' => 'pending'
        ]);

        Livewire::actingAs($kasir)
            ->test(\App\Livewire\Discount\ApprovalList::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('actionType', 'approve')
            ->set('keterangan', 'ok')
            ->call('submitAction')
            ->assertForbidden();
            
        $this->assertEquals('pending', $approval->fresh()->status);
    }

    public function test_kasir_ditolak_membuat_menu()
    {
        $kasir = $this->createKasir();

        $response = $this->actingAs($kasir)->get('/menu/create');
        $response->assertForbidden();

        Livewire::actingAs($kasir)
            ->test(\App\Livewire\Menu\Create::class)
            ->call('simpan')
            ->assertForbidden();
    }

    public function test_kasir_ditolak_membuat_pengeluaran()
    {
        $kasir = $this->createKasir();

        $response = $this->actingAs($kasir)->get('/pengeluaran/create');
        $response->assertForbidden();

        Livewire::actingAs($kasir)
            ->test(\App\Livewire\Pengeluaran\Create::class)
            ->call('simpan')
            ->assertForbidden();
    }

    public function test_kasir_ditolak_mengubah_transaksi_selesai()
    {
        $kasir = $this->createKasir();
        $pesanan = Pesanan::factory()->create(['status' => 'selesai']);

        Livewire::actingAs($kasir)
            ->test(\App\Livewire\Transaksi\Transaksi::class)
            ->set('selectedOrder', (object)['id' => $pesanan->id])
            ->set('status', 'dibatalkan')
            ->call('updateStatus')
            ->assertForbidden();
    }

    public function test_kasir_ditolak_membuka_laporan_omzet()
    {
        $kasir = $this->createKasir();

        $response = $this->actingAs($kasir)->get('/omset');
        $response->assertForbidden();
    }

    public function test_manager_dapat_melakukan_operasi_diizinkan()
    {
        $manager = $this->createManager();

        $responseOmset = $this->actingAs($manager)->get('/omset');
        $responseOmset->assertStatus(200);

        $responseMenu = $this->actingAs($manager)->get('/menu/create');
        $responseMenu->assertStatus(200);

        $responsePengeluaran = $this->actingAs($manager)->get('/pengeluaran/create');
        $responsePengeluaran->assertStatus(200);
    }
}
