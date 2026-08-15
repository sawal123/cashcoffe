<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\Pengeluaran;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceReportConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected $branchA;
    protected $branchB;
    protected $userBranchA;
    protected $userBranchB;
    protected $superadmin;
    protected $paymentCash;
    protected $paymentKomplemen;
    protected $menu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchA = Branch::create(['kode_cabang' => 'BR-A', 'nama_cabang' => 'Branch A']);
        $this->branchB = Branch::create(['kode_cabang' => 'BR-B', 'nama_cabang' => 'Branch B']);

        $this->userBranchA = User::factory()->create(['branch_id' => $this->branchA->id]);
        $this->userBranchA->assignRole('kasir');

        $this->userBranchB = User::factory()->create(['branch_id' => $this->branchB->id]);
        $this->userBranchB->assignRole('kasir');

        $this->superadmin = User::factory()->create();
        $this->superadmin->assignRole('superadmin');

        $this->paymentCash = PaymentMethod::firstOrCreate(['kode_metode' => 'cash'], ['nama_metode' => 'Cash']);
        $this->paymentKomplemen = PaymentMethod::firstOrCreate(['kode_metode' => 'komplemen'], ['nama_metode' => 'Komplemen']);

        $category = \App\Models\Category::firstOrCreate(['nama' => 'Makanan']);
        $this->menu = \App\Models\Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Test Menu',
            'harga' => 10000,
            'h_pokok' => 5000,
            'is_active' => true,
        ]);
    }

    private function createPesanan($attributes = [])
    {
        $default = [
            'kode' => 'TRX-' . uniqid(),
            'status' => 'selesai',
            'branch_id' => $this->branchA->id,
            'payment_method_id' => $this->paymentCash->id,
            'total' => 100000,
            'total_profit' => 40000,
            'discount_value' => 0,
            'created_at' => now(),
        ];

        $pesanan = Pesanan::create(array_merge($default, $attributes));

        PesananItem::create([
            'pesanans_id' => $pesanan->id,
            'menus_id' => $this->menu->id,
            'qty' => 1,
            'harga_satuan' => $pesanan->total,
            'subtotal' => $pesanan->total,
            'profit' => $pesanan->total_profit,
        ]);

        return $pesanan;
    }

    public function test_transaksi_selesai_tanpa_discount()
    {
        $this->actingAs($this->userBranchA);
        $this->createPesanan([
            'total' => 100000,
            'discount_value' => 0,
            'total_profit' => 40000,
        ]);

        Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString())
            ->assertViewHas('totalOmset', 100000)
            ->assertViewHas('totalProfit', 40000);
    }

    public function test_transaksi_selesai_dengan_global_discount()
    {
        $this->actingAs($this->userBranchA);
        $this->createPesanan([
            'total' => 100000,
            'discount_value' => 10000,
            'total_profit' => 40000,
        ]);

        Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString())
            ->assertViewHas('totalOmset', 90000)
            ->assertViewHas('totalProfit', 30000);
    }

    public function test_transaksi_diproses_dibatalkan_soft_deleted_tidak_masuk_laporan()
    {
        $this->actingAs($this->userBranchA);
        
        $this->createPesanan(['status' => 'diproses']);
        $this->createPesanan(['status' => 'dibatalkan']);
        $pesanan = $this->createPesanan(['status' => 'selesai']);
        $pesanan->delete();

        Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString())
            ->assertViewHas('totalOmset', 0)
            ->assertViewHas('totalProfit', 0)
            ->assertViewHas('netProfit', 0);
    }

    public function test_payment_komplemen_tidak_masuk_omzet_profit_jumlah_pesanan_jumlah_menu()
    {
        $this->actingAs($this->userBranchA);
        
        $this->createPesanan([
            'payment_method_id' => $this->paymentKomplemen->id,
            'total' => 100000,
            'total_profit' => 40000,
        ]);

        $component = Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString());
            
        $dataOmset = $component->get('dataOmset');
        
        $component->assertViewHas('totalOmset', 0);
        $component->assertViewHas('totalProfit', 0);
        
        $first = $dataOmset->first();
        $this->assertEquals(0, $first->jumlah_pesanan);
        $this->assertEquals(0, $first->jumlah_menu);
        
        $this->assertEquals(1, $first->jumlah_komplemen);
        $this->assertEquals(100000, $first->total_komplemen);
        
        $component->assertViewHas('totalKomplemen', 100000);
    }

    public function test_pengeluaran_mengurangi_nett_profit()
    {
        $this->actingAs($this->userBranchA);
        $this->createPesanan([
            'total' => 100000,
            'discount_value' => 0,
            'total_profit' => 30000,
        ]);

        Pengeluaran::create([
            'user_id' => $this->userBranchA->id,
            'branch_id' => $this->branchA->id,
            'title' => 'Test Pengeluaran',
            'jumlah' => 1,
            'total' => 10000,
            'tanggal_pengeluaran' => now()->toDateString(),
        ]);

        Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString())
            ->assertViewHas('totalOmset', 100000)
            ->assertViewHas('totalProfit', 30000)
            ->assertViewHas('totalPengeluaran', 10000)
            ->assertViewHas('netProfit', 20000);
    }

    public function test_agregasi_harian_dan_periode()
    {
        $this->actingAs($this->userBranchA);
        
        Carbon::setTestNow(now()->subDay());
        $this->createPesanan([
            'total' => 100000,
            'discount_value' => 10000, // net: 90000
        ]);
        $this->createPesanan([
            'total' => 200000,
            'discount_value' => 0, // net: 200000
        ]);
        Carbon::setTestNow();
        
        $this->createPesanan([
            'total' => 50000,
            'discount_value' => 5000, // net: 45000
        ]);

        $component = Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->subDays(2)->toDateString(), now()->addDay()->toDateString());
            
        $component->assertViewHas('totalOmset', 90000 + 200000 + 45000);
        
        $data = $component->get('dataOmset');
        $day1 = $data->firstWhere('tanggal', now()->subDay()->toDateString());
        $day2 = $data->firstWhere('tanggal', now()->toDateString());
        
        $this->assertEquals(2, $day1->jumlah_pesanan);
        $this->assertEquals(290000, $day1->total_omset);
        
        $this->assertEquals(1, $day2->jumlah_pesanan);
        $this->assertEquals(45000, $day2->total_omset);
    }

    public function test_filter_dateFrom_dateTo()
    {
        $this->actingAs($this->userBranchA);
        
        Carbon::setTestNow(now()->subDays(10));
        $this->createPesanan([
            'total' => 100000,
        ]);
        Carbon::setTestNow();
        
        $this->createPesanan([
            'total' => 200000,
        ]);

        Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->subDays(2)->toDateString(), now()->addDay()->toDateString())
            ->assertViewHas('totalOmset', 200000);
    }

    public function test_branch_isolation()
    {
        $this->actingAs($this->userBranchA);
        $this->createPesanan([
            'total' => 100000,
        ]);
        
        $this->actingAs($this->userBranchB);
        $this->createPesanan([
            'total' => 200000,
        ]);

        $this->actingAs($this->userBranchA);
        Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString())
            ->assertViewHas('totalOmset', 100000);

        $this->actingAs($this->userBranchB);
        Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString())
            ->assertViewHas('totalOmset', 200000);

        $this->actingAs($this->superadmin);
        Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString())
            ->assertViewHas('totalOmset', 300000);
    }

    public function test_omzet_tidak_boleh_negatif()
    {
        $this->actingAs($this->userBranchA);
        $this->createPesanan([
            'total' => 100000,
            'discount_value' => 150000, // discount > total
            'total_profit' => 40000,
        ]);

        Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString())
            ->assertViewHas('totalOmset', 0)
            ->assertViewHas('totalProfit', -110000);
    }

    public function test_estimated_profit_boleh_negatif()
    {
        $this->actingAs($this->userBranchA);
        $this->createPesanan([
            'total' => 100000,
            'discount_value' => 50000,
            'total_profit' => 40000,
        ]);

        Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString())
            ->assertViewHas('totalProfit', -10000);
    }
}
