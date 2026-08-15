<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\Pengeluaran;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RbacSeeder;
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

        $this->seed(RbacSeeder::class);

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

        $merged = array_merge($default, $attributes);
        $qty = $attributes['qty'] ?? 1;
        unset($merged['qty']);

        $pesanan = new Pesanan();
        foreach ($merged as $key => $val) {
            $pesanan->{$key} = $val;
        }
        $pesanan->save();

        PesananItem::create([
            'pesanans_id' => $pesanan->id,
            'menus_id' => $this->menu->id,
            'qty' => $qty,
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
        // 1. Fixture Branch A
        $orderA = $this->createPesanan([
            'branch_id' => $this->branchA->id,
            'total' => 100000,
            'discount_value' => 10000,
            'total_profit' => 40000,
            'qty' => 2,
        ]);

        $komplemenA = $this->createPesanan([
            'branch_id' => $this->branchA->id,
            'payment_method_id' => $this->paymentKomplemen->id,
            'total' => 50000,
            'discount_value' => 0,
            'total_profit' => 20000,
            'qty' => 1,
        ]);

        $pengeluaranA = Pengeluaran::create([
            'user_id' => $this->userBranchA->id,
            'branch_id' => $this->branchA->id,
            'title' => 'Pengeluaran Branch A',
            'jumlah' => 1,
            'total' => 10000,
            'tanggal_pengeluaran' => now()->toDateString(),
        ]);

        // 2. Fixture Branch B
        $orderB = $this->createPesanan([
            'branch_id' => $this->branchB->id,
            'total' => 200000,
            'discount_value' => 20000,
            'total_profit' => 80000,
            'qty' => 3,
        ]);

        $komplemenB = $this->createPesanan([
            'branch_id' => $this->branchB->id,
            'payment_method_id' => $this->paymentKomplemen->id,
            'total' => 70000,
            'discount_value' => 0,
            'total_profit' => 30000,
            'qty' => 1,
        ]);

        $pengeluaranB = Pengeluaran::create([
            'user_id' => $this->userBranchB->id,
            'branch_id' => $this->branchB->id,
            'title' => 'Pengeluaran Branch B',
            'jumlah' => 1,
            'total' => 15000,
            'tanggal_pengeluaran' => now()->toDateString(),
        ]);

        // Verifikasi fixture benar-benar tersimpan dengan branch_id masing-masing
        $this->assertEquals($this->branchA->id, $orderA->branch_id);
        $this->assertEquals($this->branchB->id, $orderB->branch_id);
        $this->assertEquals($this->branchA->id, $komplemenA->branch_id);
        $this->assertEquals($this->branchB->id, $komplemenB->branch_id);
        $this->assertEquals($this->branchA->id, $pengeluaranA->branch_id);
        $this->assertEquals($this->branchB->id, $pengeluaranB->branch_id);

        // 3. Verifikasi Branch A
        $this->actingAs($this->userBranchA);
        $compA = Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString());

        $compA->assertViewHas('totalOmset', 90000)
            ->assertViewHas('totalProfit', 30000)
            ->assertViewHas('totalKomplemen', 50000)
            ->assertViewHas('totalPengeluaran', 10000)
            ->assertViewHas('netProfit', 20000);

        $rowA = $compA->get('dataOmset')->first();
        $this->assertEquals(1, $rowA->jumlah_pesanan);
        $this->assertEquals(2, $rowA->jumlah_menu);
        $this->assertEquals(50000, $rowA->total_komplemen);
        $this->assertEquals(1, $rowA->jumlah_komplemen);
        $this->assertEquals(90000, $rowA->total_omset);
        $this->assertEquals(30000, $rowA->total_profit);
        $this->assertEquals(10000, $rowA->total_pengeluaran);
        $this->assertEquals(20000, $rowA->net_profit);

        // 4. Verifikasi Branch B
        $this->actingAs($this->userBranchB);
        $compB = Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString());

        $compB->assertViewHas('totalOmset', 180000)
            ->assertViewHas('totalProfit', 60000)
            ->assertViewHas('totalKomplemen', 70000)
            ->assertViewHas('totalPengeluaran', 15000)
            ->assertViewHas('netProfit', 45000);

        $rowB = $compB->get('dataOmset')->first();
        $this->assertEquals(1, $rowB->jumlah_pesanan);
        $this->assertEquals(3, $rowB->jumlah_menu);
        $this->assertEquals(70000, $rowB->total_komplemen);
        $this->assertEquals(1, $rowB->jumlah_komplemen);
        $this->assertEquals(180000, $rowB->total_omset);
        $this->assertEquals(60000, $rowB->total_profit);
        $this->assertEquals(15000, $rowB->total_pengeluaran);
        $this->assertEquals(45000, $rowB->net_profit);

        // 5. Verifikasi Superadmin (melihat seluruh agregasi Branch A + Branch B)
        $this->actingAs($this->superadmin);
        $compSuper = Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString());

        $compSuper->assertViewHas('totalOmset', 90000 + 180000)
            ->assertViewHas('totalProfit', 30000 + 60000)
            ->assertViewHas('totalKomplemen', 50000 + 70000)
            ->assertViewHas('totalPengeluaran', 10000 + 15000)
            ->assertViewHas('netProfit', (30000 + 60000) - (10000 + 15000));

        $rowSuper = $compSuper->get('dataOmset')->first();
        $this->assertEquals(2, $rowSuper->jumlah_pesanan);
        $this->assertEquals(5, $rowSuper->jumlah_menu);
        $this->assertEquals(120000, $rowSuper->total_komplemen);
        $this->assertEquals(2, $rowSuper->jumlah_komplemen);
        $this->assertEquals(270000, $rowSuper->total_omset);
        $this->assertEquals(90000, $rowSuper->total_profit);
        $this->assertEquals(25000, $rowSuper->total_pengeluaran);
        $this->assertEquals(65000, $rowSuper->net_profit);
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

    public function test_komplemen_dengan_discount_tetap_hitung_komplemen_penuh_dan_tidak_masuk_penjualan()
    {
        $this->actingAs($this->userBranchA);

        $this->createPesanan([
            'payment_method_id' => $this->paymentKomplemen->id,
            'total' => 100000,
            'discount_value' => 10000,
            'total_profit' => 40000,
        ]);

        $component = Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString());

        $dataOmset = $component->get('dataOmset');

        $component->assertViewHas('totalOmset', 0);
        $component->assertViewHas('totalProfit', 0);
        $component->assertViewHas('totalKomplemen', 100000);

        $first = $dataOmset->first();
        $this->assertEquals(0, $first->jumlah_pesanan);
        $this->assertEquals(0, $first->jumlah_menu);
        $this->assertEquals(1, $first->jumlah_komplemen);
        $this->assertEquals(100000, $first->total_komplemen);
    }

    public function test_item_level_discount_tercermin_pada_snapshot_total_dan_tidak_dipotong_ganda()
    {
        $this->actingAs($this->userBranchA);

        $this->createPesanan([
            'total' => 80000,
            'total_profit' => 25000,
            'discount_value' => 0,
        ]);

        Livewire::test(\App\Livewire\Omset\TableOmset::class)
            ->call('setDateRange', now()->toDateString(), now()->toDateString())
            ->assertViewHas('totalOmset', 80000)
            ->assertViewHas('totalProfit', 25000);
    }
}
