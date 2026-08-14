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

        $this->user = User::factory()->create();
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

        $this->ingredient = Ingredients::create([
            'nama' => 'Biji Kopi',
            'stok' => 100,
            'satuan' => 'gram',
        ]);

        MenuIngredients::create([
            'menu_id' => $this->menu->id,
            'ingredient_id' => $this->ingredient->id,
            'qty' => 10,
        ]);

        $this->member = Member::create([
            'nama' => 'Member Test',
            'phone' => '081234567890',
            'points' => 0,
            'total_pengeluaran' => 0,
        ]);
    }

    private function createTestOrder(string $status = 'diproses', ?Discount $discount = null): Pesanan
    {
        $pesanan = Pesanan::create([
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
}
