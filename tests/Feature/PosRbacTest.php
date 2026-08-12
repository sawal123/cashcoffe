<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Discount;
use App\Models\DiscountApproval;
use App\Models\Pesanan;
use App\Models\Pengeluaran;
use App\Models\Menu;
use Database\Seeders\RbacSeeder;
use Livewire\Livewire;

class PosRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(RbacSeeder::class);
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

    private function createSuperadmin()
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');
        return $user;
    }

    public function test_rbac_seeder_dapat_dijalankan_ulang_tanpa_error()
    {
        $this->seed(RbacSeeder::class);
        $this->assertTrue(true);
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
        
        $discount = Discount::create([
            'nama_diskon' => 'General',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 1000,
            'is_active' => true,
            'type' => 'general'
        ]);

        $approval = DiscountApproval::create([
            'discount_id' => $discount->id,
            'status' => 'pending',
            'kasir_id' => $kasir->id
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
        
        $discount = Discount::create([
            'nama_diskon' => 'Private',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 1000,
            'is_active' => true,
            'type' => 'private'
        ]);

        $approval = DiscountApproval::create([
            'discount_id' => $discount->id,
            'status' => 'pending',
            'kasir_id' => $kasir->id
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

    public function test_user_tanpa_permission_approval_ditolak_approve_diskon_general()
    {
        $userNoPerm = User::factory()->create(); // No roles or permissions assigned

        $discount = Discount::create([
            'nama_diskon' => 'General',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 1000,
            'is_active' => true,
            'type' => 'general'
        ]);

        $approval = DiscountApproval::create([
            'discount_id' => $discount->id,
            'status' => 'pending',
            'kasir_id' => $userNoPerm->id
        ]);

        Livewire::actingAs($userNoPerm)
            ->test(\App\Livewire\Discount\ApprovalList::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('actionType', 'approve')
            ->set('keterangan', 'ok')
            ->call('submitAction')
            ->assertForbidden();

        $this->assertEquals('pending', $approval->fresh()->status);
    }

    public function test_manager_dapat_approve_diskon_non_general()
    {
        $manager = $this->createManager();
        
        $discount = Discount::create([
            'nama_diskon' => 'Private Special',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'is_active' => true,
            'type' => 'private'
        ]);

        $approval = DiscountApproval::create([
            'discount_id' => $discount->id,
            'status' => 'pending',
            'kasir_id' => $manager->id
        ]);

        Livewire::actingAs($manager)
            ->test(\App\Livewire\Discount\ApprovalList::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('actionType', 'approve')
            ->set('keterangan', 'approved by manager')
            ->call('submitAction')
            ->assertDispatched('close-modal');

        $this->assertEquals('approved', $approval->fresh()->status);
    }
    
    public function test_kasir_ditolak_simpan_dan_update_master_diskon()
    {
        $kasir = $this->createKasir();

        $this->actingAs($kasir)->get('/discount/create')->assertForbidden();

        Livewire::actingAs($kasir)
            ->test(\App\Livewire\Discount\CreateDiscount::class)
            ->call('simpan')
            ->assertForbidden();

        $discount = Discount::create([
            'nama_diskon' => 'Test Disc',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 1000,
            'is_active' => true,
            'type' => 'general'
        ]);

        Livewire::actingAs($kasir)
            ->test(\App\Livewire\Discount\CreateDiscount::class)
            ->call('update', $discount->id)
            ->assertForbidden();
    }

    public function test_kasir_ditolak_simpan_dan_update_menu()
    {
        $kasir = $this->createKasir();

        $this->actingAs($kasir)->get('/menu/create')->assertForbidden();

        Livewire::actingAs($kasir)
            ->test(\App\Livewire\Menu\Create::class)
            ->call('simpan')
            ->assertForbidden();

        Livewire::actingAs($kasir)
            ->test(\App\Livewire\Menu\Create::class)
            ->call('update')
            ->assertForbidden();
    }

    public function test_kasir_ditolak_simpan_dan_update_pengeluaran()
    {
        $kasir = $this->createKasir();

        $this->actingAs($kasir)->get('/pengeluaran/create')->assertForbidden();

        Livewire::actingAs($kasir)
            ->test(\App\Livewire\Pengeluaran\Create::class)
            ->call('simpan')
            ->assertForbidden();

        $pengeluaran = Pengeluaran::create([
            'user_id' => $kasir->id,
            'tanggal_pengeluaran' => now()->toDateString(),
            'title' => 'Test',
            'jumlah' => 1,
            'total' => 1000,
        ]);

        Livewire::actingAs($kasir)
            ->test(\App\Livewire\Pengeluaran\Create::class)
            ->call('update', $pengeluaran->id)
            ->assertForbidden();
    }

    public function test_kasir_ditolak_mengubah_transaksi_selesai()
    {
        $kasir = $this->createKasir();
        
        $pesanan = Pesanan::create([
            'kode' => 'TRX-001',
            'nama' => 'Test',
            'status' => 'selesai',
            'total' => 10000,
            'pajak' => 0,
            'subtotal' => 10000
        ]);

        Livewire::actingAs($kasir)
            ->test(\App\Livewire\Transaksi\Transaksi::class)
            ->set('selectedOrder', $pesanan)
            ->set('status', 'dibatalkan')
            ->call('updateStatus')
            ->assertForbidden();
    }

    public function test_kasir_ditolak_membuka_laporan_omzet_dan_export()
    {
        $kasir = $this->createKasir();

        $this->actingAs($kasir)->get('/omset')->assertForbidden();
        $this->actingAs($kasir)->get('/orders/export')->assertForbidden();
    }

    public function test_manager_dapat_menjalankan_action_server_side_dan_buka_halaman()
    {
        $manager = $this->createManager();

        $this->actingAs($manager)->get('/omset')->assertStatus(200);
        $this->actingAs($manager)->get('/menu/create')->assertStatus(200);
        $this->actingAs($manager)->get('/pengeluaran/create')->assertStatus(200);
        $this->actingAs($manager)->get('/discount/create')->assertStatus(200);
        $this->actingAs($manager)->get('/orders/export')->assertStatus(200);

        // Test server-side action calls for manager
        Livewire::actingAs($manager)
            ->test(\App\Livewire\Discount\CreateDiscount::class)
            ->set('nama_diskon', 'Manager Disc')
            ->set('jenis_diskon', 'nominal')
            ->set('nilai_diskon', 500)
            ->set('is_active', 1)
            ->set('member_only', false)
            ->set('scope', 'global')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('discounts', ['nama_diskon' => 'Manager Disc']);
    }
    
    public function test_superadmin_tetap_memiliki_akses_administratif()
    {
        $superadmin = $this->createSuperadmin();

        $this->actingAs($superadmin)->get('/omset')->assertStatus(200);
        $this->actingAs($superadmin)->get('/menu/create')->assertStatus(200);
        $this->actingAs($superadmin)->get('/pengeluaran/create')->assertStatus(200);
        $this->actingAs($superadmin)->get('/discount/create')->assertStatus(200);
        $this->actingAs($superadmin)->get('/orders/export')->assertStatus(200);
    }
}
