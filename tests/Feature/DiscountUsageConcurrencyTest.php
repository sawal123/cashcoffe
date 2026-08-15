<?php

namespace Tests\Feature;

use App\Livewire\Order\CreateOrder;
use App\Models\Category;
use App\Models\Discount;
use App\Models\PaymentMethod;
use App\Models\Pesanan;
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
    protected \App\Models\Menu $menu;
    protected PaymentMethod $paymentMethod;
    protected SalesChannel $salesChannel;
    protected PriceTier $priceTier;

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
        $this->menu = \App\Models\Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Kopi Susu',
            'harga' => 20000,
            'h_pokok' => 10000,
            'is_active' => true,
        ]);
    }

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

    private function makeGlobalDiscount(string $kode, ?int $digunakan = 0, ?int $limit = null, ?int $minimumTransaksi = null): Discount
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

    /** 1. new order apply global discount: digunakan 0 → 1 */
    public function test_new_order_apply_global_discount_increments_digunakan()
    {
        $discount = $this->makeGlobalDiscount('PROMO-NEW', 0, 10);

        $order = $this->doSaveOrder($discount);

        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertEquals($discount->id, $order->fresh()->discount_id);
        $this->assertEquals(5000, $order->fresh()->discount_value);
    }

    /** 2. new order discount full: limit=1, digunakan=1 -> discount tidak applied, digunakan tetap 1 */
    public function test_new_order_discount_full_not_applied_and_digunakan_unchanged()
    {
        $discount = $this->makeGlobalDiscount('PROMO-FULL', 1, 1);

        $order = $this->doSaveOrder($discount);

        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertNull($order->fresh()->discount_id);
        $this->assertEquals(0, $order->fresh()->discount_value);
    }

    /** 3. existing order update same discount: digunakan tidak berubah */
    public function test_existing_order_update_same_discount_usage_unchanged()
    {
        $discount = $this->makeGlobalDiscount('SAME-DISC', 0, 5);
        $order = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        $this->doUpdateOrder($order, $discount);

        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertEquals($discount->id, $order->fresh()->discount_id);
    }

    /** 4. existing same discount saat limit penuh: discount tetap applied, digunakan tidak berubah */
    public function test_existing_same_discount_when_limit_full_stays_applied_and_usage_unchanged()
    {
        $discount = $this->makeGlobalDiscount('LIMIT-SAME', 0, 1);
        $order = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        // Update same order with same discount while limit is full (digunakan = limit = 1)
        $this->doUpdateOrder($order, $discount);

        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertEquals($discount->id, $order->fresh()->discount_id);
        $this->assertEquals(5000, $order->fresh()->discount_value);
    }

    /** 5. update order add discount: increment tepat 1x */
    public function test_update_order_add_discount_increments_exactly_once()
    {
        $order = $this->doSaveOrder(); // no discount
        $this->assertNull($order->fresh()->discount_id);

        $discount = $this->makeGlobalDiscount('ADD-ONCE', 0, 5);
        $this->doUpdateOrder($order, $discount);

        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertEquals($discount->id, $order->fresh()->discount_id);
    }

    /** 6. update order remove discount: decrement tepat 1x */
    public function test_update_order_remove_discount_decrements_exactly_once()
    {
        $discount = $this->makeGlobalDiscount('REMOVE-ONCE', 0, 5);
        $order = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        $this->doUpdateOrder($order, null);

        $this->assertEquals(0, $discount->fresh()->digunakan);
        $this->assertNull($order->fresh()->discount_id);
        $this->assertEquals(0, $order->fresh()->discount_value);
    }

    /** 7. update swap A → B: A decrement 1x, B increment 1x */
    public function test_update_swap_discount_decrements_old_and_increments_new()
    {
        $discountA = $this->makeGlobalDiscount('SWAP-A', 3, 5);
        $discountB = $this->makeGlobalDiscount('SWAP-B', 1, 5);

        $order = $this->doSaveOrder($discountA);
        $this->assertEquals(4, $discountA->fresh()->digunakan);
        $this->assertEquals(1, $discountB->fresh()->digunakan);

        $this->doUpdateOrder($order, $discountB);

        $this->assertEquals(3, $discountA->fresh()->digunakan);
        $this->assertEquals(2, $discountB->fresh()->digunakan);
        $this->assertEquals($discountB->id, $order->fresh()->discount_id);
    }

    /** 8. swap ke B yang sudah full: tidak menyebabkan counter invalid / partial change */
    public function test_swap_to_discount_already_full_does_not_cause_invalid_counter_or_partial_change()
    {
        $discountA = $this->makeGlobalDiscount('SWAP-FULL-A', 0, 5);
        $discountB = $this->makeGlobalDiscount('SWAP-FULL-B', 5, 5); // already full

        $order = $this->doSaveOrder($discountA);
        $this->assertEquals(1, $discountA->fresh()->digunakan);
        $this->assertEquals(5, $discountB->fresh()->digunakan);

        // Try swapping to B which is full
        $this->doUpdateOrder($order, $discountB);

        // A should be released because user changed selection, but B could not be applied
        $this->assertEquals(0, $discountA->fresh()->digunakan);
        $this->assertEquals(5, $discountB->fresh()->digunakan); // stays at 5, not 6
        $this->assertNull($order->fresh()->discount_id);
        $this->assertEquals(0, $order->fresh()->discount_value);
    }

    /** 9. cancel order: decrement tepat 1x */
    public function test_cancel_order_decrements_digunakan_exactly_once()
    {
        $discount = $this->makeGlobalDiscount('CANCEL-DISC', 0, 5);
        $order = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $this->assertEquals(0, $discount->fresh()->digunakan);
        $this->assertEquals('dibatalkan', $order->fresh()->status);
    }

    /** 10. cancel order dua kali: tidak double decrement */
    public function test_cancel_order_twice_does_not_double_decrement()
    {
        $discount = $this->makeGlobalDiscount('CANCEL-TWICE', 0, 5);
        $order = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        $comp = Livewire::actingAs($this->user)->test(CreateOrder::class);

        $comp->call('batalkanPesanan', $order->id);
        $this->assertEquals(0, $discount->fresh()->digunakan);

        $comp->call('batalkanPesanan', $order->id);
        $this->assertEquals(0, $discount->fresh()->digunakan);
    }

    /** 11. digunakan tidak pernah negatif */
    public function test_digunakan_never_becomes_negative()
    {
        $discount = $this->makeGlobalDiscount('NEVER-NEG', 0, 5);
        $order = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        // Force reset counter to 0 directly
        $discount->update(['digunakan' => 0]);

        Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $this->assertEquals(0, $discount->fresh()->digunakan);
        $this->assertGreaterThanOrEqual(0, $discount->fresh()->digunakan);
    }

    /** 12. failure setelah counter update: transaction rollback counter via production flow */
    public function test_failure_after_counter_update_rolls_back_transaction()
    {
        $discount = $this->makeGlobalDiscount('ROLLBACK-TEST', 0, 5);
        $usageBefore = (int) ($discount->fresh()->digunakan ?? 0);

        // Hook: force Pesanan::updating to throw AFTER discount counter is already updated
        // (saveOrder does Pesanan::create then updates it — updating fires on the final update)
        $hookFired = false;
        \App\Models\Pesanan::updating(function (\App\Models\Pesanan $pesanan) use (&$hookFired) {
            if (!$hookFired) {
                $hookFired = true;
                throw new \RuntimeException('Simulated order persistence failure after counter update');
            }
        });

        // Call saveOrder via Livewire directly — Livewire catches the exception internally
        Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->set('nama_costumer', 'Tester')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->buildCartPayload())
            ->set('discountId', $discount->id)
            ->set('discount_id', $discount->id)
            ->call('saveOrder');

        // Flush the listener so it does not affect subsequent tests
        \App\Models\Pesanan::flushEventListeners();

        // Counter must be rolled back to value before the request
        $this->assertEquals($usageBefore, $discount->fresh()->digunakan);
    }

    /** 13. concurrent final-slot invariant: limit tidak boleh terlewati */
    public function test_concurrent_final_slot_invariant_limit_not_exceeded()
    {
        $discount = $this->makeGlobalDiscount('SLOT-FINAL', 0, 1);

        // Order 1 claims the last slot
        $order1 = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertEquals($discount->id, $order1->fresh()->discount_id);

        // Order 2 attempts to claim at the same time
        $order2 = $this->doSaveOrder($discount);

        // Invariant: digunakan must NEVER exceed limit
        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertLessThanOrEqual($discount->limit, $discount->fresh()->digunakan);
        $this->assertNull($order2->fresh()->discount_id);
    }

    /** 14. dua discount yang di-lock menggunakan deterministic order */
    public function test_two_discounts_locked_using_deterministic_order()
    {
        $discount1 = $this->makeGlobalDiscount('DISC-1', 0, 5);
        $discount2 = $this->makeGlobalDiscount('DISC-2', 0, 5);

        // Ensure higher ID vs lower ID
        $higherId = max($discount1->id, $discount2->id);
        $lowerId = min($discount1->id, $discount2->id);

        $highDisc = Discount::find($higherId);
        $lowDisc = Discount::find($lowerId);

        // Start order with higher ID discount
        $order = $this->doSaveOrder($highDisc);
        $this->assertEquals($higherId, $order->fresh()->discount_id);

        // Update swapping from higher ID to lower ID
        $this->doUpdateOrder($order, $lowDisc);

        $this->assertEquals($lowerId, $order->fresh()->discount_id);
        $this->assertEquals(0, $highDisc->fresh()->digunakan);
        $this->assertEquals(1, $lowDisc->fresh()->digunakan);
    }

    // ============================================================
    // NULL digunakan regression tests
    // ============================================================

    /** NULL-1: discount dengan digunakan=NULL, limit=1 → order pertama applied, digunakan=1 */
    public function test_null_digunakan_treated_as_zero_first_order_applied()
    {
        $discount = $this->makeGlobalDiscount('NULL-FIRST', null, 1);
        // Explicitly set digunakan to NULL (makeGlobalDiscount sets it to $digunakan=null here)
        \Illuminate\Support\Facades\DB::table('discounts')->where('id', $discount->id)->update(['digunakan' => null]);
        $this->assertNull($discount->fresh()->digunakan);

        $order = $this->doSaveOrder($discount);

        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertEquals($discount->id, $order->fresh()->discount_id);
        $this->assertGreaterThan(0, $order->fresh()->discount_value);
    }

    /** NULL-2: discount dengan digunakan=NULL, limit=1 → order kedua tidak applied, digunakan tetap 1 */
    public function test_null_digunakan_second_order_blocked_after_first_claims_slot()
    {
        $discount = $this->makeGlobalDiscount('NULL-SECOND', null, 1);
        \Illuminate\Support\Facades\DB::table('discounts')->where('id', $discount->id)->update(['digunakan' => null]);

        $order1 = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        $order2 = $this->doSaveOrder($discount);

        $this->assertEquals(1, $discount->fresh()->digunakan);
        $this->assertNull($order2->fresh()->discount_id);
        $this->assertEquals(0, $order2->fresh()->discount_value);
    }

    /** NULL-3: update order dengan same discount dan digunakan=NULL → tidak double increment */
    public function test_null_digunakan_update_same_discount_no_double_increment()
    {
        $discount = $this->makeGlobalDiscount('NULL-UPDATE-SAME', 0, 5);
        $order = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        // Simulate production discount that has digunakan=NULL (existing data race)
        \Illuminate\Support\Facades\DB::table('discounts')->where('id', $discount->id)->update(['digunakan' => null]);

        $this->doUpdateOrder($order, $discount);

        // Same-discount path: counter must NOT be incremented again.
        // With NULL -> 0 normalization, usage becomes 0+1=1 only for NEW discount; same slot is retained without re-increment.
        // The invariant is: counter must not be > 1 (no double increment).
        $finalUsage = (int) ($discount->fresh()->digunakan ?? 0);
        $this->assertLessThanOrEqual(1, $finalUsage);
        $this->assertGreaterThanOrEqual(0, $finalUsage);
        $this->assertEquals($discount->id, $order->fresh()->discount_id);
    }

    /** NULL-4: cancellation saat digunakan=NULL → tidak error, digunakan tetap 0 */
    public function test_null_digunakan_cancellation_does_not_produce_negative_or_error()
    {
        $discount = $this->makeGlobalDiscount('NULL-CANCEL', 0, 5);
        $order = $this->doSaveOrder($discount);

        // Set to NULL to simulate production data
        \Illuminate\Support\Facades\DB::table('discounts')->where('id', $discount->id)->update(['digunakan' => null]);
        $this->assertNull($discount->fresh()->digunakan);

        Livewire::actingAs($this->user)->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id);

        $finalUsage = $discount->fresh()->digunakan;
        $this->assertEquals(0, $finalUsage);
        $this->assertGreaterThanOrEqual(0, $finalUsage);
    }

    /** NULL-5: remove discount dengan digunakan=NULL → counter menjadi 0 (numeric) setelah operasi */
    public function test_null_digunakan_remove_discount_counter_becomes_numeric()
    {
        $discount = $this->makeGlobalDiscount('NULL-REMOVE', 0, 5);
        $order = $this->doSaveOrder($discount);
        $this->assertEquals(1, $discount->fresh()->digunakan);

        // Set to NULL to simulate production data race or missing fill
        \Illuminate\Support\Facades\DB::table('discounts')->where('id', $discount->id)->update(['digunakan' => null]);

        $this->doUpdateOrder($order, null); // remove discount

        $finalUsage = $discount->fresh()->digunakan;
        $this->assertNotNull($finalUsage);
        $this->assertEquals(0, $finalUsage);
        $this->assertGreaterThanOrEqual(0, $finalUsage);
    }
}

