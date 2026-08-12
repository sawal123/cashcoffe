<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Discount;
use App\Models\DiscountApproval;
use App\Models\Pesanan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Livewire;

class PosRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $permissions = [
            'operate pos',
            'approve general discount',
            'approve all discount',
            'manage discount',
            'manage menu',
            'manage pengeluaran',
            'edit finished transaction',
            'view sensitive reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $kasir = Role::firstOrCreate(['name' => 'kasir']);

        $superadmin->givePermissionTo(Permission::all());
        
        $manager->givePermissionTo([
            'operate pos',
            'approve general discount',
            'approve all discount',
            'manage discount',
            'manage menu',
            'manage pengeluaran',
            'edit finished transaction',
            'view sensitive reports',
        ]);

        $kasir->givePermissionTo([
            'operate pos',
            'approve general discount',
        ]);
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
    
    public function test_kasir_ditolak_membuat_master_diskon()
    {
        $kasir = $this->createKasir();

        $response = $this->actingAs($kasir)->get('/discount/create');
        $response->assertForbidden();

        Livewire::actingAs($kasir)
            ->test(\App\Livewire\Discount\CreateDiscount::class)
            ->call('simpan')
            ->assertForbidden();
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

        $response = $this->actingAs($kasir)->get('/omset');
        $response->assertForbidden();
        
        $response = $this->actingAs($kasir)->get('/orders/export');
        $response->assertForbidden();
    }

    public function test_manager_dapat_melakukan_operasi_diizinkan()
    {
        $manager = $this->createManager();

        $this->actingAs($manager)->get('/omset')->assertStatus(200);
        $this->actingAs($manager)->get('/menu/create')->assertStatus(200);
        $this->actingAs($manager)->get('/pengeluaran/create')->assertStatus(200);
        $this->actingAs($manager)->get('/discount/create')->assertStatus(200);
        $this->actingAs($manager)->get('/orders/export')->assertStatus(200);
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
