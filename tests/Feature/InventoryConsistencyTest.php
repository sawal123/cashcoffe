<?php

namespace Tests\Feature;

use App\Livewire\Order\TableOrder;
use App\Livewire\Order\Traits\HandlesOrderSubmit;
use App\Livewire\Transaksi\Transaksi;
use App\Models\Category;
use App\Models\Ingredients;
use App\Models\Member;
use App\Models\Menu;
use App\Models\MenuIngredients;
use App\Models\PaymentMethod;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\PriceTier;
use App\Models\RiwayatStock;
use App\Models\SalesChannel;
use App\Models\SatuanBahan;
use App\Models\User;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use DB;

class InventoryConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected \App\Models\Branch $branch;
    protected User $user;
    protected Menu $menu;
    protected Ingredients $ingredientDasar;
    protected Ingredients $ingredientVarian;
    protected PaymentMethod $paymentMethod;
    protected SalesChannel $salesChannel;
    protected PriceTier $priceTier;
    protected Member $member;
    protected VariantOption $variantOption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->branch = \App\Models\Branch::create([
            'nama_cabang' => 'Branch Utama',
            'kode_cabang' => 'BRU',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->user->assignRole('kasir');

        $this->priceTier = PriceTier::first() ?? PriceTier::create(['nama_tier' => 'Regular', 'is_active' => true]);
        $this->salesChannel = SalesChannel::first() ?? SalesChannel::create(['nama_channel' => 'Dine In', 'is_active' => true]);
        $this->paymentMethod = PaymentMethod::first() ?? PaymentMethod::create(['nama_metode' => 'Cash', 'kode_metode' => 'tunai', 'is_active' => true]);

        $category = Category::create(['nama' => 'Makanan']);
        $this->menu = Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Kopi Susu',
            'harga' => 20000,
            'h_pokok' => 10000,
            'is_active' => true,
        ]);

        $satuan = SatuanBahan::create(['nama_satuan' => 'Gram']);

        $this->ingredientDasar = Ingredients::create([
            'branch_id' => $this->branch->id,
            'nama_bahan' => 'Biji Kopi',
            'satuan_id' => $satuan->id,
            'stok' => 100,
            'hpp' => 1000,
        ]);

        $this->ingredientVarian = Ingredients::create([
            'branch_id' => $this->branch->id,
            'nama_bahan' => 'Susu',
            'satuan_id' => $satuan->id,
            'stok' => 50,
            'hpp' => 500,
        ]);

        MenuIngredients::create([
            'menu_id' => $this->menu->id,
            'ingredient_id' => $this->ingredientDasar->id,
            'qty' => 10,
        ]);

        $vg = VariantGroup::create([
            'nama_group' => 'Topping',
            'selection_type' => 'multiple',
            'is_required' => false,
        ]);
        $this->menu->variantGroups()->attach($vg->id);

        $this->variantOption = VariantOption::create([
            'variant_group_id' => $vg->id,
            'nama_opsi' => 'Ekstra Susu',
            'extra_price' => 5000,
        ]);

        $this->variantOption->ingredients()->attach($this->ingredientVarian->id, ['qty' => 5]);

        $this->member = Member::create([
            'user_id' => $this->user->id,
            'phone' => '081234567890',
            'points' => 0,
            'total_pengeluaran' => 0,
        ]);
    }

    private function createTestOrder(int $qty = 1, bool $withVariant = false): Pesanan
    {
        $pesanan = Pesanan::create([
            'branch_id' => $this->branch->id,
            'kode' => 'ORD-' . uniqid(),
            'nama' => 'Pelanggan Test',
            'user_id' => $this->user->id,
            'member_id' => $this->member->id,
            'payment_method_id' => $this->paymentMethod->id,
            'sales_channel_id' => $this->salesChannel->id,
            'total' => 20000 * $qty + ($withVariant ? 5000 * $qty : 0),
            'total_profit' => 10000 * $qty,
            'status' => 'diproses',
        ]);

        $item = PesananItem::create([
            'pesanans_id' => $pesanan->id,
            'menus_id' => $this->menu->id,
            'qty' => $qty,
            'harga_satuan' => 20000,
            'subtotal' => 20000 * $qty + ($withVariant ? 5000 * $qty : 0),
            'discount_value' => 0,
            'profit' => 10000 * $qty,
        ]);

        if ($withVariant) {
            $item->variants()->attach($this->variantOption->id);
        }

        return $pesanan;
    }

    public function test_order_selesai_stok_resep_dasar_berkurang()
    {
        $order = $this->createTestOrder(2); // qty=2 * 10 = 20
        $this->assertEquals(100, $this->ingredientDasar->stok);

        Livewire::actingAs($this->user)->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $this->assertEquals(80, $this->ingredientDasar->fresh()->stok);
    }

    public function test_variant_ingredient_ikut_mengurangi_stok()
    {
        $order = $this->createTestOrder(2, true); // variant qty 5 * 2 = 10
        
        Livewire::actingAs($this->user)->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $this->assertEquals(80, $this->ingredientDasar->fresh()->stok);
        $this->assertEquals(40, $this->ingredientVarian->fresh()->stok);
    }

    public function test_ingredient_sama_dari_beberapa_item_diagregasi_dan_dipotong_sekali()
    {
        // Add variant option that uses the same basic ingredient
        $this->variantOption->ingredients()->attach($this->ingredientDasar->id, ['qty' => 5]);

        $order = $this->createTestOrder(1, true); // base 10 + variant 5 = 15 total
        
        Livewire::actingAs($this->user)->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $this->assertEquals(85, $this->ingredientDasar->fresh()->stok);
        $historyCount = RiwayatStock::where('ingredient_id', $this->ingredientDasar->id)->count();
        $this->assertEquals(1, $historyCount, 'Harus diagregasi jadi 1 row riwayat stok');
    }

    public function test_riwayat_stock_dibuat_tepat_1_per_ingredient()
    {
        $order = $this->createTestOrder(2, true);
        
        Livewire::actingAs($this->user)->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $this->assertEquals(1, RiwayatStock::where('ingredient_id', $this->ingredientDasar->id)->count());
        $this->assertEquals(1, RiwayatStock::where('ingredient_id', $this->ingredientVarian->id)->count());
    }

    public function test_qty_before_dan_qty_after_sesuai_stok_aktual()
    {
        $order = $this->createTestOrder(3); // qty=3 * 10 = 30
        
        Livewire::actingAs($this->user)->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $history = RiwayatStock::where('ingredient_id', $this->ingredientDasar->id)->first();
        $this->assertEquals(100, $history->qty_before);
        $this->assertEquals(30, $history->qty);
        $this->assertEquals(70, $history->qty_after);
    }

    public function test_complete_order_dipanggil_2x_stok_tidak_double_berkurang()
    {
        $order = $this->createTestOrder(1);
        
        $component = Livewire::actingAs($this->user)->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $this->assertEquals(90, $this->ingredientDasar->fresh()->stok);

        // Call again
        $component->set('status', 'selesai')
            ->call('updateStatus');

        $this->assertEquals(90, $this->ingredientDasar->fresh()->stok); // unchanged
    }

    public function test_complete_order_dipanggil_2x_riwayat_stock_tidak_double()
    {
        $order = $this->createTestOrder(1);
        
        $component = Livewire::actingAs($this->user)->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $this->assertEquals(1, RiwayatStock::count());

        $component->set('status', 'selesai')
            ->call('updateStatus');

        $this->assertEquals(1, RiwayatStock::count());
    }

    public function test_stok_ingredient_dasar_kurang_tetap_selesai_dan_stok_menjadi_negatif()
    {
        // stok = 2, order membutuhkan 5
        $this->ingredientDasar->update(['stok' => 2]);
        MenuIngredients::where('menu_id', $this->menu->id)
            ->where('ingredient_id', $this->ingredientDasar->id)
            ->update(['qty' => 5]);

        $order = $this->createTestOrder(1);

        Livewire::actingAs($this->user)->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $this->assertEquals('selesai', $order->fresh()->status);
        $this->assertEquals(-3, $this->ingredientDasar->fresh()->stok);

        $riwayat = RiwayatStock::where('ingredient_id', $this->ingredientDasar->id)->first();
        $this->assertNotNull($riwayat);
        $this->assertEquals(2, $riwayat->qty_before);
        $this->assertEquals(5, $riwayat->qty);
        $this->assertEquals(-3, $riwayat->qty_after);
        $this->assertEquals('out', $riwayat->tipe);
    }

    public function test_variant_ingredient_kurang_tetap_selesai_dan_stok_menjadi_negatif()
    {
        // Variant membutuhkan 5, stok diset ke 1 -> hasil akhir -4
        $this->ingredientVarian->update(['stok' => 1]);
        $order = $this->createTestOrder(1, true);

        Livewire::actingAs($this->user)->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $this->assertEquals('selesai', $order->fresh()->status);
        $this->assertEquals(-4, $this->ingredientVarian->fresh()->stok);

        $riwayat = RiwayatStock::where('ingredient_id', $this->ingredientVarian->id)->first();
        $this->assertNotNull($riwayat);
        $this->assertEquals(1, $riwayat->qty_before);
        $this->assertEquals(5, $riwayat->qty);
        $this->assertEquals(-4, $riwayat->qty_after);
    }

    public function test_kedua_completion_path_mengizinkan_negative_stock()
    {
        RiwayatStock::truncate();

        // 1. Path TableOrder::saji
        $this->ingredientDasar->update(['stok' => 2]);
        MenuIngredients::where('menu_id', $this->menu->id)
            ->where('ingredient_id', $this->ingredientDasar->id)
            ->update(['qty' => 5]);

        $order1 = $this->createTestOrder(1);
        Livewire::actingAs($this->user)->test(TableOrder::class)
            ->call('saji', base64_encode($order1->id));

        $this->assertEquals('selesai', $order1->fresh()->status);
        $this->assertEquals(-3, $this->ingredientDasar->fresh()->stok);

        // 2. Path CreateOrder::completeLastOrder()
        Ingredients::where('id', $this->ingredientDasar->id)->update(['stok' => 2]);
        $order2 = $this->createTestOrder(1);
        Livewire::actingAs($this->user)->test(\App\Livewire\Order\CreateOrder::class)
            ->set('lastPesananId', $order2->id)
            ->call('completeLastOrder');

        $this->assertEquals('selesai', $order2->fresh()->status);
        $this->assertEquals(-3, $this->ingredientDasar->fresh()->stok);
    }

    public function test_penyelesaian_order_dua_kali_pada_table_order_dan_create_order_tidak_double_potong()
    {
        RiwayatStock::truncate();
        Ingredients::where('id', $this->ingredientDasar->id)->update(['stok' => 100]);
        MenuIngredients::where('menu_id', $this->menu->id)
            ->where('ingredient_id', $this->ingredientDasar->id)
            ->update(['qty' => 10]);

        $order = $this->createTestOrder(1);

        // Call saji pertama kali
        $comp = Livewire::actingAs($this->user)->test(TableOrder::class)
            ->call('saji', base64_encode($order->id));
        $this->assertEquals(90, $this->ingredientDasar->fresh()->stok);
        $this->assertEquals(1, RiwayatStock::count());

        // Call saji kedua kali
        $comp->call('saji', base64_encode($order->id));
        $this->assertEquals(90, $this->ingredientDasar->fresh()->stok);
        $this->assertEquals(1, RiwayatStock::count());

        // Call completeLastOrder pada order yang sama
        Livewire::actingAs($this->user)->test(\App\Livewire\Order\CreateOrder::class)
            ->set('lastPesananId', $order->id)
            ->call('completeLastOrder');
        $this->assertEquals(90, $this->ingredientDasar->fresh()->stok);
        $this->assertEquals(1, RiwayatStock::count());
    }

    public function test_role_admin_dapat_melihat_dan_menjalankan_aksi_order()
    {
        $roleAdmin = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create(['branch_id' => $this->branch->id]);
        $admin->assignRole('admin');

        $orderDiproses = $this->createTestOrder(1);

        $orderSelesai = $this->createTestOrder(1);
        $orderSelesai->update(['status' => 'selesai']);

        // Test rendering view untuk role admin
        $testComponent = Livewire::actingAs($admin)->test(TableOrder::class);

        // Order diproses: Wajib ada Tandai Selesai, Print Struk, Edit Pesanan, dan TIDAK ada tombol Hapus
        $testComponent->assertSeeHtml("saji('" . base64_encode($orderDiproses->id) . "')")
            ->assertSeeHtml('title="Tandai Selesai"')
            ->assertSeeHtml(route('struk.print', base64_encode($orderDiproses->id)))
            ->assertSeeHtml('title="Print Struk"')
            ->assertSeeHtml("/order/" . base64_encode($orderDiproses->id) . "/edit")
            ->assertSeeHtml('title="Edit Pesanan"')
            ->assertDontSeeHtml('title="Hapus Pesanan"');

        // Order selesai: Wajib ada Lihat Detail, Print Struk, dan Edit Pesanan
        $testComponent->assertSeeHtml("showDetail('" . base64_encode($orderSelesai->id) . "')")
            ->assertSeeHtml('title="Lihat Detail"')
            ->assertSeeHtml(route('struk.print', base64_encode($orderSelesai->id)))
            ->assertSeeHtml("/order/" . base64_encode($orderSelesai->id) . "/edit");

        // Order dibatalkan: Wajib ada Edit Pesanan
        $orderBatal = $this->createTestOrder(1);
        $orderBatal->update(['status' => 'dibatalkan']);
        $testComponentBatal = Livewire::actingAs($admin)->test(TableOrder::class);
        $testComponentBatal->assertSeeHtml("/order/" . base64_encode($orderBatal->id) . "/edit")
            ->assertDontSeeHtml('title="Hapus Pesanan"');

        // Test action saji berfungsi untuk role admin
        $testComponent->call('saji', base64_encode($orderDiproses->id));
        $this->assertEquals('selesai', $orderDiproses->fresh()->status);
    }

    public function test_ketiga_completion_path_menghasilkan_perubahan_stok_dan_history_yang_sama()
    {
        // Reset DB for clean state
        RiwayatStock::truncate();

        // 1. Path Transaksi::updateStatus
        Ingredients::where('id', $this->ingredientDasar->id)->update(['stok' => 100]);
        $order1 = $this->createTestOrder(1);
        Livewire::actingAs($this->user)->test(Transaksi::class)
            ->set('selectedOrder', $order1)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');
        
        $this->assertEquals(90, $this->ingredientDasar->fresh()->stok);

        // 2. Path TableOrder::saji
        Ingredients::where('id', $this->ingredientDasar->id)->update(['stok' => 100]);
        $order2 = $this->createTestOrder(1);
        Livewire::actingAs($this->user)->test(TableOrder::class)
            ->call('saji', base64_encode($order2->id));
        
        $this->assertEquals(90, $this->ingredientDasar->fresh()->stok);

        // 3. Path CreateOrder::completeLastOrder()
        Ingredients::where('id', $this->ingredientDasar->id)->update(['stok' => 100]);
        $order3 = $this->createTestOrder(1);
        Livewire::actingAs($this->user)->test(\App\Livewire\Order\CreateOrder::class)
            ->set('lastPesananId', $order3->id)
            ->call('completeLastOrder');
        
        $this->assertEquals(90, $this->ingredientDasar->fresh()->stok);
    }

    public function test_missing_ingredient_rollback_dan_tidak_mengubah_point_pengeluaran()
    {
        $order = $this->createTestOrder(1, false); // Hanya butuh ingredientDasar

        $this->ingredientDasar->update(['stok' => 100]);
        
        // Simulasikan missing ingredient dengan soft delete ingredientDasar.
        // Karena ingredientDasar diambil via MenuIngredients yang tidak mengecek soft delete,
        // stockChanges akan berisi ID ingredientDasar, namun query Ingredients::whereIn() akan mengabaikannya,
        // sehingga memicu exception "Bahan baku dengan ID {id} tidak ditemukan."
        $this->ingredientDasar->delete();

        $startPoints = $this->member->points;
        $startPengeluaran = $this->member->total_pengeluaran;

        Livewire::actingAs($this->user)->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        // Completion gagal
        $this->assertEquals('diproses', $order->fresh()->status);

        // Stok Ingredient A tidak berubah (rollback)
        $this->assertEquals(100, $this->ingredientDasar->fresh()->stok);

        // Tidak ada RiwayatStock baru
        $this->assertEquals(0, RiwayatStock::count());

        // Member points tidak berubah
        $this->assertEquals($startPoints, $this->member->fresh()->points);
        $this->assertEquals($startPengeluaran, $this->member->fresh()->total_pengeluaran);
    }

    public function test_missing_ingredient_variant_rollback_dan_tidak_mengubah_point_pengeluaran()
    {
        $order = $this->createTestOrder(1, true); // Membutuhkan ingredientDasar & ingredientVarian

        $this->ingredientDasar->update(['stok' => 100]);
        
        $this->ingredientVarian->delete();
        
        $pivotExists = \Illuminate\Support\Facades\DB::table('variant_option_ingredients')
            ->where('variant_option_id', $this->variantOption->id)
            ->where('ingredient_id', $this->ingredientVarian->id)
            ->exists();
        $this->assertTrue($pivotExists, 'Pivot reference should still exist after soft-delete');

        $startPoints = $this->member->points;
        $startPengeluaran = $this->member->total_pengeluaran;

        Livewire::actingAs($this->user)->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $this->assertEquals('diproses', $order->fresh()->status);
        $this->assertEquals(100, $this->ingredientDasar->fresh()->stok);
        $this->assertEquals(0, RiwayatStock::count());
        $this->assertEquals($startPoints, $this->member->fresh()->points);
        $this->assertEquals($startPengeluaran, $this->member->fresh()->total_pengeluaran);
    }
}
