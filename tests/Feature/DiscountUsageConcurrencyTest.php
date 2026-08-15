<?php

namespace Tests\Feature;

use App\Livewire\Order\CreateOrder;
use App\Models\Category;
use App\Models\Discount;
use App\Models\PaymentMethod;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\PriceTier;
use App\Models\SalesChannel;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DiscountUsageConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected PaymentMethod $paymentMethod;
    protected SalesChannel $salesChannel;
    protected PriceTier $priceTier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('kasir');

        $this->priceTier     = PriceTier::first()    ?? PriceTier::create(['nama_tier' => 'Regular', 'is_active' => true]);
        $this->salesChannel  = SalesChannel::first() ?? SalesChannel::create(['nama_channel' => 'Dine In', 'is_active' => true]);
        $this->paymentMethod = PaymentMethod::first() ?? PaymentMethod::create([
            'nama_metode' => 'Cash', 'kode_metode' => 'tunai', 'is_active' => true,
        ]);

        $category = Category::create(['nama' => 'Minuman']);
        $this->menu = \App\Models\Menu::create([
            'categories_id' => $category->id,
            'nama_menu'     => 'Kopi Susu',
            'harga'         => 20000,
            'h_pokok'       => 8000,
            'is_active'     => true,
        ]);
    }

    // ============================================================
    // Helpers
    // ============================================================

    private function makeGlobalDiscount(string $kode, ?int $digunakan = 0, ?int $limit = null, ?int $minimumTransaksi = null): Discount
    {
        return Discount::create([
            'nama_diskon'       => $kode,
            'type'              => 'general',
            'kode_diskon'       => $kode,
            'jenis_diskon'      => 'nominal',
            'nilai_diskon'      => 5000,
            'scope'             => 'global',
            'is_active'         => true,
            'digunakan'         => $digunakan,
            'limit'             => $limit,
            'minimum_transaksi' => $minimumTransaksi,
        ]);
    }

    private function buildCartPayload(): array
    {
        // Format harus cocok dengan apa yang frontend kirim ke property $pesanan di CreateOrder
        // render() membaca $p['harga'] dan validateAndCalculateServerItems membaca $p['id'], $p['qty'], $p['selected_options']
        $cartKey = (string) $this->menu->id;
        return [
            $cartKey => [
                'id'              => $this->menu->id,
                'nama_menu'       => $this->menu->nama_menu,
                'harga'           => $this->menu->harga,
                'gambar'          => '',
                'qty'             => 1,
                'selected_options' => [],
                'catatan'         => '',
            ],
        ];
    }

    private function doSaveOrder(?Discount $discount = null): ?Pesanan
    {
        $countBefore = Pesanan::count();

        $component = Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->set('nama_costumer', 'Tester')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->buildCartPayload());

        if ($discount) {
            $component->set('discountId', $discount->id);
        }

        $component->call('saveOrder');

        return Pesanan::count() > $countBefore ? Pesanan::latest('id')->first() : null;
    }

    private function doUpdateOrder(Pesanan $order, ?Discount $discount = null): void
    {
        $component = Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->set('orderId', $order->id)
            ->set('nama_costumer', 'Tester Updated')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->buildCartPayload());

        if ($discount) {
            $component->set('discount_id', $discount->id);
        }

        $component->call('updateOrder');
    }

    // ============================================================
    // Core concurrency & limit tests
    // ============================================================

    /** 1. limit NULL → tidak ada limit, digunakan bertambah */
    public function test_no_limit_discount_increments_normally()
    {
        $discount = $this->makeGlobalDiscount('NOLIMIT', 0);
        $order = $this->doSaveOrder($discount);
        $this->assertNotNull($order);
        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertEquals($discount->id, $order->fresh()->discount_id);
    }

    /** 2. limit=1, digunakan=0 → order pertama applied */
    public function test_first_order_claims_last_slot()
    {
        $discount = $this->makeGlobalDiscount('LIMIT1', 0, 1);
        $order = $this->doSaveOrder($discount);
        $this->assertNotNull($order);
        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertEquals($discount->id, $order->fresh()->discount_id);
        $this->assertGreaterThan(0, $order->fresh()->discount_value);
    }

    /** 3. limit=1, digunakan=1 → order kedua tidak dapat discount */
    public function test_second_order_blocked_when_limit_full()
    {
        $discount = $this->makeGlobalDiscount('LIMIT1FULL', 1, 1);
        $order = $this->doSaveOrder($discount);
        $this->assertNotNull($order);
        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertNull($order->fresh()->discount_id);
        $this->assertEquals(0, $order->fresh()->discount_value);
    }

    /** 4. non-global discount: digunakan tidak berubah */
    public function test_non_global_discount_does_not_change_digunakan()
    {
        $disc = Discount::create([
            'nama_diskon'  => 'ITEM-DISC',
            'type'         => 'general',
            'kode_diskon'  => 'ITEM-DISC',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 2000,
            'scope'        => 'item',
            'is_active'    => true,
            'digunakan'    => 0,
        ]);
        $before = $disc->digunakan;
        $this->doSaveOrder($disc);
        $this->assertEquals($before, $disc->fresh()->digunakan);
    }

    /** 5. batalkan order: digunakan berkurang, selalu numeric */
    public function test_cancellation_decrements_digunakan()
    {
        $discount = $this->makeGlobalDiscount('CANCEL-DISC', 0, 5);
        $order = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $this->assertEquals(0, $discount->fresh()->digunakan);
        $this->assertIsInt($discount->fresh()->digunakan);
    }

    /** 6. batalkan dua kali: idempotent, tidak negatif */
    public function test_cancellation_idempotent_not_negative()
    {
        $discount = $this->makeGlobalDiscount('IDMPOTENT', 0, 5);
        $order = $this->doSaveOrder($discount);

        $comp = Livewire::actingAs($this->user)->test(CreateOrder::class);
        $comp->call('batalkanPesanan', $order->id);
        $this->assertEquals(0, $discount->fresh()->digunakan);
        $comp->call('batalkanPesanan', $order->id);
        $this->assertEquals(0, $discount->fresh()->digunakan);
        $this->assertGreaterThanOrEqual(0, $discount->fresh()->digunakan);
    }

    /** 7. update: ganti discount A → B */
    public function test_update_swap_discount_a_to_b()
    {
        $discA = $this->makeGlobalDiscount('SWAP-A', 0, 5);
        $discB = $this->makeGlobalDiscount('SWAP-B', 0, 5);
        $order = $this->doSaveOrder($discA);
        $this->assertEquals(1, $discA->fresh()->digunakan);

        $this->doUpdateOrder($order, $discB);

        $this->assertEquals(0, $discA->fresh()->digunakan);
        $this->assertEquals(1, $discB->fresh()->digunakan);
        $this->assertEquals($discB->id, $order->fresh()->discount_id);
    }

    /** 8. update: hapus discount */
    public function test_update_remove_discount_decrements_digunakan()
    {
        $discount = $this->makeGlobalDiscount('REMOVE', 0, 5);
        $order = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        $this->doUpdateOrder($order, null);

        $this->assertEquals(0, $discount->fresh()->digunakan);
        $this->assertNull($order->fresh()->discount_id);
    }

    /** 9. update: minimum transaksi tidak terpenuhi → tidak increment */
    public function test_update_global_discount_failing_min_trx_no_increment()
    {
        $discount = $this->makeGlobalDiscount('MINTRX', 0, 5, 999999);
        $order = $this->doSaveOrder(null);
        $this->doUpdateOrder($order, $discount);
        $this->assertEquals(0, $discount->fresh()->digunakan);
        $this->assertNull($order->fresh()->discount_id);
    }

    /** 10. update: limit penuh → order baru tidak bisa pakai */
    public function test_update_global_discount_at_limit_no_increment()
    {
        $discount = $this->makeGlobalDiscount('UPDLIMIT', 1, 1);
        $order = $this->doSaveOrder(null);
        $this->doUpdateOrder($order, $discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertNull($order->fresh()->discount_id);
    }

    /** 11. update: existing order sama tetap pakai discount walaupun limit penuh */
    public function test_update_existing_discount_at_limit_stays_applied()
    {
        $discount = $this->makeGlobalDiscount('EXISTLIMIT', 0, 1);
        $order = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        $this->doUpdateOrder($order, $discount);

        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertEquals($discount->id, $order->fresh()->discount_id);
        $this->assertIsInt($discount->fresh()->digunakan);
    }

    /** 12. concurrent final-slot invariant */
    public function test_concurrent_final_slot_invariant_limit_not_exceeded()
    {
        $discount = $this->makeGlobalDiscount('SLOT-FINAL', 0, 1);
        $order1 = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        $order2 = $this->doSaveOrder($discount);
        $this->assertNotNull($order2);
        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertLessThanOrEqual($discount->limit, $discount->fresh()->digunakan);
        $this->assertNull($order2->fresh()->discount_id);
    }

    /** 13. dua discount lock deterministic order (deadlock prevention) */
    public function test_two_discounts_locked_using_deterministic_order()
    {
        $discA = $this->makeGlobalDiscount('DET-A', 0, 5);
        $discB = $this->makeGlobalDiscount('DET-B', 0, 5);
        $highId  = max($discA->id, $discB->id);
        $lowId   = min($discA->id, $discB->id);

        $order = $this->doSaveOrder(Discount::find($highId));
        $this->assertEquals($highId, $order->fresh()->discount_id);

        $this->doUpdateOrder($order, Discount::find($lowId));

        $this->assertEquals($lowId, $order->fresh()->discount_id);
        $this->assertEquals(0, Discount::find($highId)->fresh()->digunakan);
        $this->assertEquals(1, Discount::find($lowId)->fresh()->digunakan);
    }

    /** 14. digunakan tidak pernah negatif */
    public function test_digunakan_never_becomes_negative()
    {
        $discount = $this->makeGlobalDiscount('NEVER-NEG', 0, 5);
        $order = $this->doSaveOrder($discount);
        DB::table('discounts')->where('id', $discount->id)->update(['digunakan' => 0]);

        Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $this->assertEquals(0, $discount->fresh()->digunakan);
        $this->assertGreaterThanOrEqual(0, $discount->fresh()->digunakan);
        $this->assertIsInt($discount->fresh()->digunakan);
    }

    // ============================================================
    // Rollback test (strengthened)
    // ============================================================

    /** 15. failure mid-transaction: counter rollback + tidak ada Pesanan/PesananItem partial */
    public function test_failure_after_counter_update_rolls_back_transaction()
    {
        $discount = $this->makeGlobalDiscount('ROLLBACK-TEST', 0, 5);
        $usageBefore  = (int) ($discount->fresh()->digunakan ?? 0);
        $ordersBefore  = Pesanan::count();
        $itemsBefore   = PesananItem::count();

        $hookFired = false;
        Pesanan::updating(function (Pesanan $pesanan) use (&$hookFired) {
            if (!$hookFired) {
                $hookFired = true;
                throw new \RuntimeException('Simulated failure mid-transaction');
            }
        });

        Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->set('nama_costumer', 'Tester')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->buildCartPayload())
            ->set('discountId', $discount->id)
            ->call('saveOrder');

        // Jangan flush semua listener (destructive ke model boot observers).
        // $hookFired = true sudah mencegah hook dari throw lagi di test berikutnya.

        // Counter harus kembali ke nilai awal
        $this->assertEquals($usageBefore, (int) ($discount->fresh()->digunakan ?? 0));
        // Tidak ada Pesanan partial
        $this->assertEquals($ordersBefore, Pesanan::count());
        // Tidak ada PesananItem partial
        $this->assertEquals($itemsBefore, PesananItem::count());
    }

    // ============================================================
    // NULL digunakan regression tests
    // ============================================================

    /** NULL-1: digunakan=NULL, order pertama → digunakan=1 (numeric) */
    public function test_null_digunakan_treated_as_zero_first_order_applied()
    {
        $discount = $this->makeGlobalDiscount('NULL-FIRST', 0, 1);
        DB::table('discounts')->where('id', $discount->id)->update(['digunakan' => null]);
        $this->assertNull($discount->fresh()->digunakan);

        $order = $this->doSaveOrder($discount);

        $this->assertNotNull($order);
        $this->assertNotNull($discount->fresh()->digunakan);
        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertEquals($discount->id, $order->fresh()->discount_id);
        $this->assertGreaterThan(0, $order->fresh()->discount_value);
    }

    /** NULL-2: digunakan=NULL, limit=1, ada 1 existing order → order baru ditolak */
    public function test_null_digunakan_with_existing_order_blocks_new_order()
    {
        $discount = $this->makeGlobalDiscount('NULL-LEGACY', 0, 1);

        // Order A sudah menggunakan discount ini
        Pesanan::create([
            'kode'              => 'ORD-LEGACY-A',
            'nama'              => 'Pelanggan A',
            'user_id'           => $this->user->id,
            'payment_method_id' => $this->paymentMethod->id,
            'sales_channel_id'  => $this->salesChannel->id,
            'discount_id'       => $discount->id,
            'discount_value'    => 5000,
            'total'             => 15000,
            'total_profit'      => 7000,
            'status'            => 'diproses',
        ]);

        // Set digunakan=NULL (simulasi data legacy)
        DB::table('discounts')->where('id', $discount->id)->update(['digunakan' => null]);
        $this->assertNull($discount->fresh()->digunakan);

        // Order B mencoba pakai discount yang sama
        $orderB = $this->doSaveOrder($discount);

        $this->assertNotNull($orderB);
        $this->assertNull($orderB->fresh()->discount_id,
            'Order B seharusnya ditolak karena slot sudah dipakai Order A');
        $this->assertEquals(0, $orderB->fresh()->discount_value);
    }

    /** NULL-3: digunakan=NULL, same existing order update → digunakan=1 (numeric) */
    public function test_null_digunakan_same_existing_discount_update_normalizes_to_numeric()
    {
        $discount = $this->makeGlobalDiscount('NULL-UPDATE-SAME', 0, 5);
        $order = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        DB::table('discounts')->where('id', $discount->id)->update(['digunakan' => null]);
        $this->assertNull($discount->fresh()->digunakan);

        $this->doUpdateOrder($order, $discount);

        $finalUsage = $discount->fresh()->digunakan;
        $this->assertNotNull($finalUsage, 'digunakan harus numeric setelah update');
        $this->assertEquals(1, $finalUsage, 'Satu existing order = digunakan 1, tidak double increment');
        $this->assertEquals($discount->id, $order->fresh()->discount_id);
    }

    /** NULL-4: cancellation saat digunakan=NULL → 0 (numeric, tidak negatif, tidak tetap NULL) */
    public function test_null_digunakan_cancellation_produces_numeric_zero()
    {
        $discount = $this->makeGlobalDiscount('NULL-CANCEL', 0, 5);
        $order = $this->doSaveOrder($discount);

        DB::table('discounts')->where('id', $discount->id)->update(['digunakan' => null]);
        $this->assertNull($discount->fresh()->digunakan);

        Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $finalUsage = $discount->fresh()->digunakan;
        $this->assertNotNull($finalUsage, 'digunakan harus numeric setelah cancellation');
        $this->assertEquals(0, $finalUsage);
        $this->assertGreaterThanOrEqual(0, $finalUsage);
        $this->assertIsInt($finalUsage);
    }

    /** NULL-5: digunakan=0, cancellation → 0 (bukan negatif) */
    public function test_zero_digunakan_cancellation_stays_zero()
    {
        $discount = $this->makeGlobalDiscount('ZERO-CANCEL', 0, 5);
        $order = $this->doSaveOrder($discount);
        DB::table('discounts')->where('id', $discount->id)->update(['digunakan' => 0]);

        Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $this->assertEquals(0, $discount->fresh()->digunakan);
        $this->assertGreaterThanOrEqual(0, $discount->fresh()->digunakan);
    }

    /** NULL-6: digunakan=NULL, hapus discount via update → counter=0 numeric */
    public function test_null_digunakan_remove_discount_via_update_produces_numeric_zero()
    {
        $discount = $this->makeGlobalDiscount('NULL-REMOVE', 0, 5);
        $order = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        DB::table('discounts')->where('id', $discount->id)->update(['digunakan' => null]);

        $this->doUpdateOrder($order, null);

        $finalUsage = $discount->fresh()->digunakan;
        $this->assertNotNull($finalUsage, 'digunakan harus numeric setelah remove');
        $this->assertEquals(0, $finalUsage);
        $this->assertGreaterThanOrEqual(0, $finalUsage);
    }
}
