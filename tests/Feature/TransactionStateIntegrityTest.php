<?php

namespace Tests\Feature;

use App\Livewire\Order\CreateOrder;
use App\Livewire\Order\TableOrder;
use App\Livewire\Transaksi\Transaksi;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Ingredients;
use App\Models\Member;
use App\Models\Menu;
use App\Models\MenuIngredients;
use App\Models\PaymentMethod;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\PriceTier;
use App\Models\SalesChannel;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TransactionStateIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected \App\Models\Branch $branch;
    protected User $user;
    protected Menu $menu;
    protected Ingredients $ingredient;
    protected PaymentMethod $paymentMethod;
    protected SalesChannel $salesChannel;
    protected PriceTier $priceTier;
    protected Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->priceTier = PriceTier::first() ?? PriceTier::create(['nama_tier' => 'Regular', 'is_active' => true]);

        $this->branch = \App\Models\Branch::create([
            'nama_cabang' => 'Cabang Test',
            'kode_cabang' => 'CBT',
            'price_tier_id' => $this->priceTier->id,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->user->assignRole('kasir');
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

        $satuan = \App\Models\SatuanBahan::create([
            'nama_satuan' => 'Gram',
        ]);

        $this->ingredient = Ingredients::create([
            'branch_id' => $this->branch->id,
            'nama_bahan' => 'Biji Kopi',
            'satuan_id' => $satuan->id,
            'stok' => 100,
            'hpp' => 1000,
        ]);

        MenuIngredients::create([
            'menu_id' => $this->menu->id,
            'ingredient_id' => $this->ingredient->id,
            'qty' => 10,
        ]);

        $this->member = Member::create([
            'user_id' => $this->user->id,
            'phone' => '081234567890',
            'points' => 0,
            'total_pengeluaran' => 0,
        ]);
    }

    private function createTestOrder(string $status = 'diproses', ?Discount $discount = null): Pesanan
    {
        $pesanan = Pesanan::create([
            'branch_id' => $this->branch->id,
            'kode' => 'ORD-' . uniqid(),
            'nama' => 'Pelanggan Test',
            'user_id' => $this->user->id,
            'member_id' => $this->member->id,
            'payment_method_id' => $this->paymentMethod->id,
            'sales_channel_id' => $this->salesChannel->id,
            'discount_id' => $discount?->id,
            'discount_value' => $discount ? 5000 : 0,
            'total' => 20000,
            'total_profit' => 10000,
            'status' => $status,
        ]);

        PesananItem::create([
            'pesanans_id' => $pesanan->id,
            'menus_id' => $this->menu->id,
            'qty' => 1,
            'harga_satuan' => 20000,
            'subtotal' => 20000,
            'discount_value' => 0,
            'profit' => 10000,
        ]);

        return $pesanan;
    }

    /** 1. diproses -> selesai berhasil */
    public function test_diproses_to_selesai_succeeds()
    {
        $order = $this->createTestOrder('diproses');

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $order->refresh();
        $this->assertEquals('selesai', $order->status);
        $this->assertEquals(90, $this->ingredient->fresh()->stok);
        $this->assertEquals(2, $this->member->fresh()->points);
    }

    /** 2. diproses -> dibatalkan berhasil */
    public function test_diproses_to_dibatalkan_succeeds()
    {
        $order = $this->createTestOrder('diproses');

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'dibatalkan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $order->refresh();
        $this->assertEquals('dibatalkan', $order->status);
        $this->assertEquals(100, $this->ingredient->fresh()->stok);
        $this->assertEquals(0, $this->member->fresh()->points);
    }

    /** 3. selesai -> diproses ditolak */
    public function test_selesai_to_diproses_rejected()
    {
        $order = $this->createTestOrder('selesai');

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'diproses')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $order->refresh();
        $this->assertEquals('selesai', $order->status);
    }

    /** 4. selesai -> dibatalkan ditolak */
    public function test_selesai_to_dibatalkan_rejected()
    {
        $order = $this->createTestOrder('selesai');

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'dibatalkan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $order->refresh();
        $this->assertEquals('selesai', $order->status);
    }

    /** 5. dibatalkan -> selesai ditolak */
    public function test_dibatalkan_to_selesai_rejected()
    {
        $order = $this->createTestOrder('dibatalkan');

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $order->refresh();
        $this->assertEquals('dibatalkan', $order->status);
    }

    /** 6. dibatalkan -> diproses ditolak */
    public function test_dibatalkan_to_diproses_rejected()
    {
        $order = $this->createTestOrder('dibatalkan');

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'diproses')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $order->refresh();
        $this->assertEquals('dibatalkan', $order->status);
    }

    /** 7. updateOrder() pada selesai ditolak dan item tidak berubah */
    public function test_updateOrder_on_selesai_rejected()
    {
        $order = $this->createTestOrder('selesai');
        $itemCountBefore = $order->items()->count();

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->call('editOrder', $order->id)
            ->call('updateOrder');

        $this->assertEquals('selesai', $order->fresh()->status);
        $this->assertEquals($itemCountBefore, $order->fresh()->items()->count());
    }

    /** 8. updateOrder() pada dibatalkan ditolak dan item tidak berubah */
    public function test_updateOrder_on_dibatalkan_rejected()
    {
        $order = $this->createTestOrder('dibatalkan');
        $itemCountBefore = $order->items()->count();

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->call('editOrder', $order->id)
            ->call('updateOrder');

        $this->assertEquals('dibatalkan', $order->fresh()->status);
        $this->assertEquals($itemCountBefore, $order->fresh()->items()->count());
    }

    /** 9. action selesai dipanggil 2 kali -> stok hanya terpotong sekali */
    public function test_complete_action_called_twice_stock_reduced_once()
    {
        $order = $this->createTestOrder('diproses');

        $component = Livewire::actingAs($this->user)
            ->test(TableOrder::class)
            ->call('saji', base64_encode($order->id));

        $this->assertEquals(90, $this->ingredient->fresh()->stok);

        // Call again
        $component->call('saji', base64_encode($order->id));

        $this->assertEquals(90, $this->ingredient->fresh()->stok);
    }

    /** 10. action selesai dipanggil 2 kali -> point/member tidak double */
    public function test_complete_action_called_twice_member_points_not_duplicated()
    {
        $order = $this->createTestOrder('diproses');

        $component = Livewire::actingAs($this->user)
            ->test(TableOrder::class)
            ->call('saji', base64_encode($order->id));

        $this->assertEquals(2, $this->member->fresh()->points);

        // Call again
        $component->call('saji', base64_encode($order->id));

        $this->assertEquals(2, $this->member->fresh()->points);
    }

    /** 11. action selesai dipanggil 2 kali -> penggunaan discount tidak double */
    public function test_complete_action_called_twice_discount_usage_not_duplicated()
    {
        $discount = Discount::create([
            'nama_diskon' => 'Promo 5K',
            'type' => 'fixed',
            'kode_diskon' => 'PROMO5K',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
            'digunakan' => 1,
        ]);

        $order = $this->createTestOrder('diproses', $discount);

        $component = Livewire::actingAs($this->user)
            ->test(TableOrder::class)
            ->call('saji', base64_encode($order->id));

        $this->assertEquals(1, $discount->fresh()->digunakan);

        // Call again
        $component->call('saji', base64_encode($order->id));

        $this->assertEquals(1, $discount->fresh()->digunakan);
    }

    /** 12. action batal dipanggil 2 kali -> tidak double restore/decrement */
    public function test_cancel_action_called_twice_no_double_decrement()
    {
        $discount = Discount::create([
            'nama_diskon' => 'Promo 10',
            'type' => 'fixed',
            'kode_diskon' => 'PROMO10',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
            'digunakan' => 1,
        ]);

        $order = $this->createTestOrder('diproses', $discount);

        $component = Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $this->assertEquals(0, $discount->fresh()->digunakan);
        $this->assertEquals('dibatalkan', $order->fresh()->status);

        // Call again
        $component->call('batalkanPesanan', $order->id);

        $this->assertEquals(0, $discount->fresh()->digunakan);
        $this->assertEquals('dibatalkan', $order->fresh()->status);
    }

    /** 13. invalid transition tidak mengubah pesanan_items, total, stock, member, atau discount */
    public function test_invalid_transition_leaves_data_untouched()
    {
        $discount = Discount::create([
            'nama_diskon' => 'Promo Fix',
            'type' => 'fixed',
            'kode_diskon' => 'PROMOFIX',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
            'digunakan' => 1,
        ]);

        $order = $this->createTestOrder('selesai', $discount);
        $stockBefore = $this->ingredient->fresh()->stok;
        $pointsBefore = $this->member->fresh()->points;
        $discUsageBefore = $discount->fresh()->digunakan;
        $totalBefore = $order->total;

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'diproses')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $order->refresh();
        $this->assertEquals('selesai', $order->status);
        $this->assertEquals($totalBefore, $order->total);
        $this->assertEquals($stockBefore, $this->ingredient->fresh()->stok);
        $this->assertEquals($pointsBefore, $this->member->fresh()->points);
        $this->assertEquals($discUsageBefore, $discount->fresh()->digunakan);
    }

    /** 14. test memastikan status yang dimanipulasi melalui Livewire tidak dapat melewati status aktual database */
    public function test_livewire_client_status_tampering_cannot_bypass_db_status()
    {
        $order = $this->createTestOrder('selesai');

        // Client passes selectedOrder with fake status 'diproses'
        $fakeSelectedOrder = clone $order;
        $fakeSelectedOrder->status = 'diproses';

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $fakeSelectedOrder)
            ->set('status', 'dibatalkan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        // Status in DB remains 'selesai'
        $this->assertEquals('selesai', $order->fresh()->status);
    }

    /** Regression 1: batalkanPesanan() tetap menyimpan discount_id dan discount_value */
    public function test_batalkanPesanan_retains_discount_id_and_discount_value()
    {
        $discount = Discount::create([
            'nama_diskon' => 'Promo Diskon',
            'type' => 'fixed',
            'kode_diskon' => 'PROMOFULL',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
            'digunakan' => 1,
        ]);

        $order = $this->createTestOrder('diproses', $discount);

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $order->refresh();
        $this->assertEquals('dibatalkan', $order->status);
        $this->assertEquals($discount->id, $order->discount_id);
        $this->assertEquals(5000, $order->discount_value);
    }

    /** Regression 2: Transaksi::updateStatus() cancellation menghasilkan snapshot diskon yang sama */
    public function test_transaksi_updateStatus_cancellation_retains_identical_discount_snapshot()
    {
        $discount = Discount::create([
            'nama_diskon' => 'Promo Diskon',
            'type' => 'fixed',
            'kode_diskon' => 'PROMOFULL2',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
            'digunakan' => 1,
        ]);

        $order = $this->createTestOrder('diproses', $discount);

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'dibatalkan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $order->refresh();
        $this->assertEquals('dibatalkan', $order->status);
        $this->assertEquals($discount->id, $order->discount_id);
        $this->assertEquals(5000, $order->discount_value);
    }

    /** Regression 3: cancellation dari kedua jalur menghasilkan state final order yang konsisten */
    public function test_both_cancellation_paths_produce_consistent_final_order_state()
    {
        $discount = Discount::create([
            'nama_diskon' => 'Promo Diskon',
            'type' => 'fixed',
            'kode_diskon' => 'PROMOBOTH',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
            'digunakan' => 2,
        ]);

        $orderA = $this->createTestOrder('diproses', $discount);
        $orderB = $this->createTestOrder('diproses', $discount);

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->call('batalkanPesanan', $orderA->id);

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $orderB)
            ->set('status', 'dibatalkan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $orderA->refresh();
        $orderB->refresh();

        $this->assertEquals($orderA->status, $orderB->status);
        $this->assertEquals($orderA->discount_id, $orderB->discount_id);
        $this->assertEquals($orderA->discount_value, $orderB->discount_value);
        $this->assertEquals($orderA->total, $orderB->total);
        $this->assertEquals(0, $discount->fresh()->digunakan);
    }

    /** Regression 4: non-global discount tidak mengurangi digunakan jika flow create tidak pernah increment */
    public function test_non_global_discount_does_not_decrement_digunakan_on_cancel()
    {
        $discount = Discount::create([
            'nama_diskon' => 'Menu Discount',
            'type' => 'fixed',
            'kode_diskon' => 'MENUDISC',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'item',
            'is_active' => true,
            'digunakan' => 5,
        ]);

        $order = $this->createTestOrder('diproses', $discount);

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $this->assertEquals(5, $discount->fresh()->digunakan);
    }

    /** Regression 5: global discount gagal minimum transaksi tidak mengurangi digunakan */
    public function test_global_discount_failing_minimum_transaction_does_not_decrement_digunakan()
    {
        $discount = Discount::create([
            'nama_diskon' => 'Min Trx Discount',
            'type' => 'fixed',
            'kode_diskon' => 'MINTRX',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
            'digunakan' => 5,
        ]);

        // Order created with discount attached but discount_value = 0 (minimum transaction failed)
        $order = Pesanan::create([
            'kode' => 'ORD-' . uniqid(),
            'nama' => 'Pelanggan Test',
            'user_id' => $this->user->id,
            'member_id' => $this->member->id,
            'payment_method_id' => $this->paymentMethod->id,
            'sales_channel_id' => $this->salesChannel->id,
            'discount_id' => $discount->id,
            'discount_value' => 0,
            'total' => 20000,
            'total_profit' => 10000,
            'status' => 'diproses',
        ]);

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $this->assertEquals(5, $discount->fresh()->digunakan);
    }

    /** Regression 6: global discount yang benar-benar diterapkan -> cancel mengurangi digunakan tepat 1x */
    public function test_valid_applied_global_discount_decrements_digunakan_exactly_once()
    {
        $discount = Discount::create([
            'nama_diskon' => 'Valid Discount',
            'type' => 'fixed',
            'kode_diskon' => 'VALIDDISC',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
            'digunakan' => 3,
        ]);

        $order = $this->createTestOrder('diproses', $discount);

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $this->assertEquals(2, $discount->fresh()->digunakan);
    }

    /** Regression 7: cancel kedua kali tidak mengurangi usage lagi */
    public function test_repeat_cancel_does_not_decrement_usage_again()
    {
        $discount = Discount::create([
            'nama_diskon' => 'Valid Discount 2',
            'type' => 'fixed',
            'kode_diskon' => 'VALIDDISC2',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
            'digunakan' => 3,
        ]);

        $order = $this->createTestOrder('diproses', $discount);

        $component = Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $this->assertEquals(2, $discount->fresh()->digunakan);

        // Cancel again
        $component->call('batalkanPesanan', $order->id);
        $this->assertEquals(2, $discount->fresh()->digunakan);
    }

    /** Regression 8: digunakan tidak pernah menjadi negatif */
    public function test_digunakan_never_becomes_negative()
    {
        $discount = Discount::create([
            'nama_diskon' => 'Zero Discount',
            'type' => 'fixed',
            'kode_diskon' => 'ZERODISC',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
            'digunakan' => 0,
        ]);

        $order = $this->createTestOrder('diproses', $discount);

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $this->assertEquals(0, $discount->fresh()->digunakan);
    }

    /** Regression 9: invalid/final-state transition tidak mengubah discount snapshot atau usage */
    public function test_invalid_transition_does_not_alter_discount_snapshot_or_usage()
    {
        $discount = Discount::create([
            'nama_diskon' => 'Final Test Disc',
            'type' => 'fixed',
            'kode_diskon' => 'FINALTEST',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
            'digunakan' => 5,
        ]);

        $order = $this->createTestOrder('selesai', $discount);

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'dibatalkan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $order->refresh();
        $this->assertEquals('selesai', $order->status);
        $this->assertEquals($discount->id, $order->discount_id);
        $this->assertEquals(5000, $order->discount_value);
        $this->assertEquals(5, $discount->fresh()->digunakan);
    }

    // =========================================================
    // updateOrder() discount accounting end-to-end tests
    // =========================================================

    private function buildCartPayload(): array
    {
        $cartKey = (string) $this->menu->id;
        return [
            $cartKey => [
                'id' => $this->menu->id,
                'nama_menu' => $this->menu->nama_menu,
                'harga' => $this->menu->harga,
                'gambar' => '',
                'qty' => 1,
                'catatan' => null,
                'status' => null,
                'selected_options' => [],
                'display_options' => [],
            ],
        ];
    }

    private function doSaveOrder(?Discount $discount = null): Pesanan
    {
        $component = Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->set('nama_costumer', 'Tester')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->buildCartPayload());

        if ($discount) {
            $component->set('discountId', $discount->id)
                ->set('discount_id', $discount->id);
        }

        $component->call('saveOrder');

        return Pesanan::latest('id')->first();
    }

    private function doUpdateOrder(Pesanan $order, ?Discount $discount = null): void
    {
        $component = Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->call('editOrder', $order->id)
            ->set('nama_costumer', 'Tester Updated')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->buildCartPayload());

        if ($discount) {
            $component->set('discount_id', $discount->id);
        } else {
            $component->set('discount_id', null);
        }

        $component->call('updateOrder');
    }

    private function makeGlobalDiscount(string $kode, int $digunakan = 0, ?int $limit = null, ?int $minimumTransaksi = null): Discount
    {
        return Discount::create([
            'nama_diskon' => $kode,
            'type' => 'fixed',
            'kode_diskon' => $kode,
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
            'digunakan' => $digunakan,
            'limit' => $limit,
            'minimum_transaksi' => $minimumTransaksi,
        ]);
    }

    /** UD-1: Create tanpa diskon → update tambah global discount → digunakan +1 */
    public function test_update_add_global_discount_increments_digunakan()
    {
        $order = $this->doSaveOrder();
        $this->assertNull($order->discount_id);

        $discount = $this->makeGlobalDiscount('ADD-DISC', 0);
        $this->doUpdateOrder($order, $discount);

        $this->assertEquals(1, $discount->fresh()->digunakan);
        $order->refresh();
        $this->assertEquals($discount->id, $order->discount_id);
        $this->assertGreaterThan(0, $order->discount_value);
    }

    /** UD-2: Setelah UD-1 dibatalkan → digunakan kembali ke 0 */
    public function test_cancel_after_update_add_discount_restores_digunakan()
    {
        $order = $this->doSaveOrder();
        $discount = $this->makeGlobalDiscount('CANCEL-AFTER-ADD', 0);
        $this->doUpdateOrder($order, $discount);

        $this->assertEquals(1, $discount->fresh()->digunakan);

        // Cancel
        Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $this->assertEquals(0, $discount->fresh()->digunakan);
    }

    /** UD-3: Create dengan Promo A → update ganti Promo B → A -1, B +1 */
    public function test_update_swap_discount_decrements_old_increments_new()
    {
        $promoA = $this->makeGlobalDiscount('PROMO-A', 5);
        $order = $this->doSaveOrder($promoA);
        $startA = $promoA->fresh()->digunakan;

        $promoB = $this->makeGlobalDiscount('PROMO-B', 2);
        $this->doUpdateOrder($order, $promoB);

        $this->assertEquals($startA - 1, $promoA->fresh()->digunakan);
        $this->assertEquals(3, $promoB->fresh()->digunakan);
    }

    /** UD-4: Update tetap memakai Promo A → counter tidak berubah */
    public function test_update_same_discount_counter_unchanged()
    {
        $promoA = $this->makeGlobalDiscount('SAME-A', 5);
        $order = $this->doSaveOrder($promoA);
        $countAfterCreate = $promoA->fresh()->digunakan;

        $this->doUpdateOrder($order, $promoA);

        $this->assertEquals($countAfterCreate, $promoA->fresh()->digunakan);
    }

    /** UD-5: Create dengan Promo A → update hapus discount → A -1 */
    public function test_update_remove_discount_decrements_digunakan()
    {
        $promoA = $this->makeGlobalDiscount('REMOVE-A', 5);
        $order = $this->doSaveOrder($promoA);
        $countAfterCreate = $promoA->fresh()->digunakan;

        $this->doUpdateOrder($order, null); // remove discount

        $this->assertEquals($countAfterCreate - 1, $promoA->fresh()->digunakan);
        $order->refresh();
        $this->assertEquals(0, $order->discount_value);
    }

    /** UD-6: Update global discount gagal minimum transaksi → tidak increment */
    public function test_update_global_discount_failing_min_trx_no_increment()
    {
        $order = $this->doSaveOrder();
        // menu harga = 20000, set minimum 999999 sehingga pasti gagal
        $disc = $this->makeGlobalDiscount('MIN-TRX-FAIL', 3, null, 999999);

        $this->doUpdateOrder($order, $disc);

        $this->assertEquals(3, $disc->fresh()->digunakan);
        $order->refresh();
        $this->assertEquals(0, $order->discount_value);
    }

    /** UD-7: Update global discount sudah mencapai limit → tidak increment */
    public function test_update_global_discount_at_limit_no_increment()
    {
        $order = $this->doSaveOrder();
        $disc = $this->makeGlobalDiscount('LIMIT-REACHED', 5, 5); // digunakan == limit

        $this->doUpdateOrder($order, $disc);

        $this->assertEquals(5, $disc->fresh()->digunakan);
        $order->refresh();
        $this->assertEquals(0, $order->discount_value);
    }

    /** UD-8: Non-global discount saat update → tidak mengubah digunakan */
    public function test_update_non_global_discount_no_digunakan_change()
    {
        $order = $this->doSaveOrder();
        $nonGlobal = Discount::create([
            'nama_diskon' => 'Item Disc',
            'type' => 'fixed',
            'kode_diskon' => 'ITEM-DISC-UPD',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'item',
            'is_active' => true,
            'digunakan' => 7,
        ]);

        $this->doUpdateOrder($order, $nonGlobal);

        $this->assertEquals(7, $nonGlobal->fresh()->digunakan);
    }

    /** UD-9: Repeat update dengan state yang sama tidak menyebabkan double increment/decrement */
    public function test_repeat_update_same_state_no_double_change()
    {
        $disc = $this->makeGlobalDiscount('REPEAT-SAME', 2);
        $order = $this->doSaveOrder($disc);
        $countAfterCreate = $disc->fresh()->digunakan;

        $this->doUpdateOrder($order, $disc);
        $this->assertEquals($countAfterCreate, $disc->fresh()->digunakan);

        $this->doUpdateOrder($order, $disc);
        $this->assertEquals($countAfterCreate, $disc->fresh()->digunakan);
    }

    // =========================================================
    // updateOrder() existing-discount at full limit edge cases
    // =========================================================

    /**
     * UD-10: Existing order uses a discount that is now at limit → update same discount keeps it applied.
     * limit=1, digunakan=0 → saveOrder → digunakan=1 → updateOrder same discount → still applied, digunakan=1.
     */
    public function test_update_existing_discount_at_limit_stays_applied()
    {
        $disc = $this->makeGlobalDiscount('FULL-LIMIT-SAME', 0, 1); // limit=1
        $order = $this->doSaveOrder($disc);

        // After saveOrder, digunakan should be 1 = limit
        $this->assertEquals(1, $disc->fresh()->digunakan);

        // Update same order with same discount
        $this->doUpdateOrder($order, $disc);

        $order->refresh();
        $this->assertEquals($disc->id, $order->discount_id);
        $this->assertGreaterThan(0, $order->discount_value);
        // Usage must not change: still 1
        $this->assertEquals(1, $disc->fresh()->digunakan);
    }

    /**
     * UD-11: Order without discount tries to add a discount already at full limit → not applied, digunakan unchanged.
     */
    public function test_update_new_order_cannot_add_full_limit_discount()
    {
        $disc = $this->makeGlobalDiscount('FULL-LIMIT-NEW', 5, 5); // digunakan == limit

        $order = $this->doSaveOrder(); // no discount
        $this->assertNull($order->discount_id);

        $this->doUpdateOrder($order, $disc);

        $order->refresh();
        $this->assertNull($order->discount_id);
        $this->assertEquals(0, $order->discount_value);
        $this->assertEquals(5, $disc->fresh()->digunakan); // unchanged
    }

    /**
     * UD-12: Existing order uses discount at full limit; after update total drops below minimum_transaksi.
     * Expected: discount removed, usage decremented exactly 1x.
     */
    public function test_update_existing_discount_at_limit_min_trx_fails_usage_decremented()
    {
        // menu harga = 20000 (standard from setUp)
        // minimum_transaksi = 999999 → will fail after update
        $disc = $this->makeGlobalDiscount('FULL-LIMIT-MIN-TRX', 0, 1); // limit=1
        // Override minimum_transaksi to be high so it fails during update
        $disc->update(['minimum_transaksi' => 999999]);

        // For saveOrder to initially apply the discount, temporarily lower minimum
        $disc->update(['minimum_transaksi' => null]);
        $order = $this->doSaveOrder($disc);
        $usageAfterCreate = $disc->fresh()->digunakan; // should be 1

        // Restore high minimum before updateOrder
        $disc->update(['minimum_transaksi' => 999999]);

        $this->doUpdateOrder($order, $disc);

        $order->refresh();
        // Discount should no longer be applied (min_trx fails)
        $this->assertEquals(0, $order->discount_value);
        // Usage should be decremented once
        $this->assertEquals($usageAfterCreate - 1, $disc->fresh()->digunakan);
    }

    /**
     * Regression: selesai -> selesai + ganti payment_method_id berhasil,
     * stok, loyalty, dan diskon tidak berubah, status tetap selesai.
     */
    public function test_selesai_to_selesai_update_payment_method_succeeds_without_side_effects()
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $newPaymentMethod = PaymentMethod::where('kode_metode', 'qris')->firstOrFail();

        $order = $this->createTestOrder('selesai');
        $initialStock = $this->ingredient->fresh()->stok;
        $initialPoints = $this->member->fresh()->points;

        Livewire::actingAs($manager)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $newPaymentMethod->id)
            ->call('updateStatus')
            ->assertDispatched('close-modal', name: 'edit-status-order')
            ->assertDispatched('showToast', type: 'success');

        $order->refresh();
        $this->assertEquals('selesai', $order->status);
        $this->assertEquals($newPaymentMethod->id, $order->payment_method_id);
        $this->assertEquals($initialStock, $this->ingredient->fresh()->stok);
        $this->assertEquals($initialPoints, $this->member->fresh()->points);
    }

    /**
     * Regression: selesai -> selesai ditolak 403 jika user tidak memiliki permission 'edit finished transaction'.
     */
    public function test_selesai_to_selesai_unauthorized_user_aborts_403()
    {
        $newPaymentMethod = PaymentMethod::where('kode_metode', 'transfer')->firstOrFail();

        $order = $this->createTestOrder('selesai');

        Livewire::actingAs($this->user) // kasir does not have 'edit finished transaction'
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $newPaymentMethod->id)
            ->call('updateStatus')
            ->assertStatus(403);

        $order->refresh();
        $this->assertEquals('selesai', $order->status);
        $this->assertEquals($this->paymentMethod->id, $order->payment_method_id);
    }

    /**
     * Regression: diproses -> diproses + ganti metode = sukses tanpa inventory deduction dan loyalty.
     */
    public function test_diproses_to_diproses_update_payment_method_succeeds_without_inventory_deduction()
    {
        $newPaymentMethod = PaymentMethod::where('kode_metode', 'transfer')->firstOrFail();

        $order = $this->createTestOrder('diproses');
        $initialStock = $this->ingredient->fresh()->stok;
        $initialPoints = $this->member->fresh()->points;

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'diproses')
            ->set('metode_pembayaran', $newPaymentMethod->id)
            ->call('updateStatus')
            ->assertDispatched('close-modal', name: 'edit-status-order')
            ->assertDispatched('showToast', type: 'success');

        $order->refresh();
        $this->assertEquals('diproses', $order->status);
        $this->assertEquals($newPaymentMethod->id, $order->payment_method_id);
        $this->assertEquals($initialStock, $this->ingredient->fresh()->stok);
        $this->assertEquals($initialPoints, $this->member->fresh()->points);
    }

    /**
     * Selesai -> diproses berhasil untuk user manager.
     */
    public function test_selesai_to_diproses_by_manager_succeeds()
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $order = $this->createTestOrder('selesai');
        $initialStock = $this->ingredient->fresh()->stok;
        $initialPoints = $this->member->fresh()->points;

        Livewire::actingAs($manager)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'diproses')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus')
            ->assertDispatched('close-modal', name: 'edit-status-order')
            ->assertDispatched('showToast', type: 'success', message: 'Transaksi berhasil diperbarui');

        $order->refresh();
        $this->assertEquals('diproses', $order->status);
        $this->assertEquals($initialStock, $this->ingredient->fresh()->stok);
        $this->assertEquals($initialPoints, $this->member->fresh()->points);
    }

    /**
     * Selesai -> diproses berhasil untuk user superadmin / admin.
     */
    public function test_selesai_to_diproses_by_superadmin_succeeds()
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $order = $this->createTestOrder('selesai');
        $initialStock = $this->ingredient->fresh()->stok;
        $initialPoints = $this->member->fresh()->points;

        Livewire::actingAs($superadmin)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'diproses')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus')
            ->assertDispatched('close-modal', name: 'edit-status-order')
            ->assertDispatched('showToast', type: 'success', message: 'Transaksi berhasil diperbarui');

        $order->refresh();
        $this->assertEquals('diproses', $order->status);
        $this->assertEquals($initialStock, $this->ingredient->fresh()->stok);
        $this->assertEquals($initialPoints, $this->member->fresh()->points);
    }

    /**
     * Regression: selesai -> dibatalkan tetap ditolak oleh validasi transisi meskipun memiliki permission.
     */
    public function test_selesai_to_dibatalkan_rejected_even_with_permission()
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $order = $this->createTestOrder('selesai');

        Livewire::actingAs($manager)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'dibatalkan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus')
            ->assertDispatched('showToast', type: 'error', message: "Transisi status dari 'selesai' ke 'dibatalkan' tidak valid.");

        $order->refresh();
        $this->assertEquals('selesai', $order->status);
    }

    /**
     * Regression: diproses -> selesai tetap bekerja normal (inventory terpotong, loyalty bertambah).
     */
    public function test_diproses_to_selesai_works_normally_with_inventory_and_loyalty()
    {
        $newPaymentMethod = PaymentMethod::where('kode_metode', 'qris')->firstOrFail();

        $order = $this->createTestOrder('diproses');
        $initialStock = $this->ingredient->fresh()->stok;

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $newPaymentMethod->id)
            ->call('updateStatus')
            ->assertDispatched('close-modal', name: 'edit-status-order')
            ->assertDispatched('showToast', type: 'success');

        $order->refresh();
        $this->assertEquals('selesai', $order->status);
        $this->assertEquals($newPaymentMethod->id, $order->payment_method_id);
        $this->assertEquals($initialStock - 10, $this->ingredient->fresh()->stok);
        $this->assertEquals(2, $this->member->fresh()->points);
    }

    /**
     * Regression: selesai -> selesai tidak menduplikasi potongan stok, poin loyalty, atau kuota diskon.
     */
    public function test_selesai_to_selesai_does_not_duplicate_stock_points_or_discount_usage()
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $discount = Discount::create([
            'nama_diskon' => 'Promo Diskon Selesai',
            'type' => 'fixed',
            'kode_diskon' => 'PROMOSL',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
            'digunakan' => 1,
        ]);

        $order = $this->createTestOrder('selesai', $discount);
        $stockBefore = $this->ingredient->fresh()->stok;
        $pointsBefore = $this->member->fresh()->points;
        $usageBefore = $discount->fresh()->digunakan;

        $newPaymentMethod = PaymentMethod::where('kode_metode', 'qris')->firstOrFail();

        Livewire::actingAs($manager)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $newPaymentMethod->id)
            ->call('updateStatus');

        $order->refresh();
        $this->assertEquals('selesai', $order->status);
        $this->assertEquals($newPaymentMethod->id, $order->payment_method_id);
        $this->assertEquals($stockBefore, $this->ingredient->fresh()->stok);
        $this->assertEquals($pointsBefore, $this->member->fresh()->points);
        $this->assertEquals($usageBefore, $discount->fresh()->digunakan);
    }
}
