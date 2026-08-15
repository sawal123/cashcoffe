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
use App\Models\SatuanBahan;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberLoyaltyConsistencyTest extends TestCase
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

        $this->priceTier    = PriceTier::first()    ?? PriceTier::create(['nama_tier' => 'Regular', 'is_active' => true]);
        $this->salesChannel = SalesChannel::first() ?? SalesChannel::create(['nama_channel' => 'Dine In', 'is_active' => true]);
        $this->paymentMethod = PaymentMethod::first() ?? PaymentMethod::create(['nama_metode' => 'Cash', 'kode_metode' => 'tunai', 'is_active' => true]);

        $category = Category::create(['nama' => 'Makanan']);
        $this->menu = Menu::create([
            'categories_id' => $category->id,
            'nama_menu'     => 'Kopi Susu',
            'harga'         => 20000,
            'h_pokok'       => 10000,
            'is_active'     => true,
        ]);

        $satuan = SatuanBahan::create(['nama_satuan' => 'Gram']);

        $this->ingredient = Ingredients::create([
            'nama_bahan' => 'Biji Kopi',
            'satuan_id'  => $satuan->id,
            'stok'       => 1000,
            'hpp'        => 1000,
        ]);

        MenuIngredients::create([
            'menu_id'       => $this->menu->id,
            'ingredient_id' => $this->ingredient->id,
            'qty'           => 10,
        ]);

        $this->member = Member::create([
            'user_id'           => $this->user->id,
            'phone'             => '081234567890',
            'points'            => 0,
            'total_pengeluaran' => 0,
        ]);
    }

    private function createOrder(int $total = 20000, int $discountValue = 0, ?int $memberId = null, string $status = 'diproses'): Pesanan
    {
        $pesanan = Pesanan::create([
            'kode'              => 'ORD-' . uniqid(),
            'nama'              => 'Pelanggan Test',
            'user_id'           => $this->user->id,
            'member_id'         => $memberId ?? $this->member->id,
            'payment_method_id' => $this->paymentMethod->id,
            'sales_channel_id'  => $this->salesChannel->id,
            'discount_id'       => null,
            'discount_value'    => $discountValue,
            'total'             => $total,
            'total_profit'      => 10000,
            'status'            => $status,
        ]);

        PesananItem::create([
            'pesanans_id'    => $pesanan->id,
            'menus_id'       => $this->menu->id,
            'qty'            => 1,
            'harga_satuan'   => $total,
            'subtotal'       => $total,
            'discount_value' => 0,
            'profit'         => 10000,
        ]);

        return $pesanan;
    }

    /** 1. final spending 9999 -> points +0 */
    public function test_final_spending_9999_gives_zero_points()
    {
        $order = $this->createOrder(9999, 0);

        $order->applyMemberLoyaltyOnCompletion();

        $this->assertEquals(0, $this->member->fresh()->points);
        $this->assertEquals(9999, $this->member->fresh()->total_pengeluaran);
    }

    /** 2. final spending 10000 -> points +1 */
    public function test_final_spending_10000_gives_one_point()
    {
        $order = $this->createOrder(10000, 0);

        $order->applyMemberLoyaltyOnCompletion();

        $this->assertEquals(1, $this->member->fresh()->points);
        $this->assertEquals(10000, $this->member->fresh()->total_pengeluaran);
    }

    /** 3. final spending 19999 -> points +1 */
    public function test_final_spending_19999_gives_one_point()
    {
        $order = $this->createOrder(19999, 0);

        $order->applyMemberLoyaltyOnCompletion();

        $this->assertEquals(1, $this->member->fresh()->points);
        $this->assertEquals(19999, $this->member->fresh()->total_pengeluaran);
    }

    /** 4. final spending 20000 -> points +2 */
    public function test_final_spending_20000_gives_two_points()
    {
        $order = $this->createOrder(20000, 0);

        $order->applyMemberLoyaltyOnCompletion();

        $this->assertEquals(2, $this->member->fresh()->points);
        $this->assertEquals(20000, $this->member->fresh()->total_pengeluaran);
    }

    /** 5. transaksi dengan discount: total 30000, discount_value 10000 -> final spending 20000, points +2, total_pengeluaran +20000 */
    public function test_transaction_with_discount_calculates_correct_loyalty()
    {
        $order = $this->createOrder(30000, 10000);

        $order->applyMemberLoyaltyOnCompletion();

        $this->assertEquals(2, $this->member->fresh()->points);
        $this->assertEquals(20000, $this->member->fresh()->total_pengeluaran);
    }

    /** 6. order tanpa member: completion sukses tanpa error */
    public function test_order_without_member_succeeds_without_error()
    {
        $pesanan = Pesanan::create([
            'kode'              => 'ORD-NO-MEMBER',
            'nama'              => 'Anonim',
            'user_id'           => $this->user->id,
            'member_id'         => null,
            'payment_method_id' => $this->paymentMethod->id,
            'sales_channel_id'  => $this->salesChannel->id,
            'discount_id'       => null,
            'discount_value'    => 0,
            'total'             => 20000,
            'total_profit'      => 10000,
            'status'            => 'diproses',
        ]);

        PesananItem::create([
            'pesanans_id'    => $pesanan->id,
            'menus_id'       => $this->menu->id,
            'qty'            => 1,
            'harga_satuan'   => 20000,
            'subtotal'       => 20000,
            'discount_value' => 0,
            'profit'         => 10000,
        ]);

        $pesanan->applyMemberLoyaltyOnCompletion();

        // Member awal points tetap 0
        $this->assertEquals(0, $this->member->fresh()->points);
    }

    /** 7. member existing: points existing tidak direset (awal 10, earned 2 => expected 12) */
    public function test_existing_member_points_accumulate_without_reset()
    {
        $this->member->update(['points' => 10]);

        $order = $this->createOrder(20000, 0);
        $order->applyMemberLoyaltyOnCompletion();

        $this->assertEquals(12, $this->member->fresh()->points);
    }

    /** 8. total_pengeluaran existing tidak direset (awal 50000, spending 20000 => expected 70000) */
    public function test_existing_member_total_pengeluaran_accumulates_without_reset()
    {
        $this->member->update(['total_pengeluaran' => 50000]);

        $order = $this->createOrder(20000, 0);
        $order->applyMemberLoyaltyOnCompletion();

        $this->assertEquals(70000, $this->member->fresh()->total_pengeluaran);
    }

    /** 9. Transaksi::updateStatus() -> loyalty tepat 1x */
    public function test_transaksi_updatestatus_path_awards_loyalty_exactly_once()
    {
        $order = $this->createOrder(20000, 0);

        Livewire::actingAs($this->user)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $this->assertEquals('selesai', $order->fresh()->status);
        $this->assertEquals(2, $this->member->fresh()->points);
        $this->assertEquals(20000, $this->member->fresh()->total_pengeluaran);
    }

    /** 10. TableOrder::saji() -> loyalty tepat 1x */
    public function test_tableorder_saji_path_awards_loyalty_exactly_once()
    {
        $order = $this->createOrder(20000, 0);

        Livewire::actingAs($this->user)
            ->test(TableOrder::class)
            ->call('saji', base64_encode((string) $order->id));

        $this->assertEquals('selesai', $order->fresh()->status);
        $this->assertEquals(2, $this->member->fresh()->points);
        $this->assertEquals(20000, $this->member->fresh()->total_pengeluaran);
    }

    /** 11. CreateOrder::completeLastOrder() -> loyalty tepat 1x */
    public function test_createorder_completelastorder_path_awards_loyalty_exactly_once()
    {
        $order = $this->createOrder(20000, 0);

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('lastPesananId', $order->id)
            ->call('completeLastOrder');

        $this->assertEquals('selesai', $order->fresh()->status);
        $this->assertEquals(2, $this->member->fresh()->points);
        $this->assertEquals(20000, $this->member->fresh()->total_pengeluaran);
    }

    /** 12. complete order yang sama dua kali: points & total_pengeluaran hanya bertambah 1x */
    public function test_repeated_completion_on_same_order_does_not_double_count_loyalty()
    {
        $order = $this->createOrder(20000, 0);

        $comp = Livewire::actingAs($this->user)
            ->test(TableOrder::class);

        $comp->call('saji', base64_encode((string) $order->id));
        $this->assertEquals(2, $this->member->fresh()->points);
        $this->assertEquals(20000, $this->member->fresh()->total_pengeluaran);

        // Call saji second time
        $comp->call('saji', base64_encode((string) $order->id));
        $this->assertEquals(2, $this->member->fresh()->points);
        $this->assertEquals(20000, $this->member->fresh()->total_pengeluaran);
    }

    /** 13. order dibatalkan kemudian completion attempt: points & total_pengeluaran tidak berubah */
    public function test_cancelled_order_completion_attempt_does_not_award_loyalty()
    {
        $order = $this->createOrder(20000, 0, null, 'dibatalkan');

        Livewire::actingAs($this->user)
            ->test(TableOrder::class)
            ->call('saji', base64_encode((string) $order->id));

        $this->assertEquals('dibatalkan', $order->fresh()->status);
        $this->assertEquals(0, $this->member->fresh()->points);
        $this->assertEquals(0, $this->member->fresh()->total_pengeluaran);
    }

    /** 14. member awal points=10, total_pengeluaran=100000. Completion kemudian inventory gagal -> rollback */
    public function test_failed_completion_due_to_stock_rolls_back_member_loyalty()
    {
        $this->member->update([
            'points'            => 10,
            'total_pengeluaran' => 100000,
        ]);

        $order = $this->createOrder(20000, 0);

        // Soft delete ingredient so processInventoryDeduction throws missing ingredient exception
        $this->ingredient->delete();

        Livewire::actingAs($this->user)
            ->test(TableOrder::class)
            ->call('saji', base64_encode((string) $order->id));

        // Completion fails and rolls back
        $this->assertEquals('diproses', $order->fresh()->status);
        $this->assertEquals(10, $this->member->fresh()->points);
        $this->assertEquals(100000, $this->member->fresh()->total_pengeluaran);
    }

    /** 15. dua order berbeda untuk member sama: Order A (earned 2), Order B (earned 3) -> initial + 5 & spending A + B */
    public function test_concurrency_invariant_two_orders_for_same_member()
    {
        $this->member->update([
            'points'            => 5,
            'total_pengeluaran' => 100000,
        ]);

        $orderA = $this->createOrder(20000, 0); // final 20000 -> earned 2
        $orderB = $this->createOrder(30000, 0); // final 30000 -> earned 3

        Livewire::actingAs($this->user)
            ->test(TableOrder::class)
            ->call('saji', base64_encode((string) $orderA->id));

        Livewire::actingAs($this->user)
            ->test(TableOrder::class)
            ->call('saji', base64_encode((string) $orderB->id));

        $this->assertEquals(10, $this->member->fresh()->points);
        $this->assertEquals(150000, $this->member->fresh()->total_pengeluaran);
    }

    /** 16. points = 0, total_pengeluaran = 0 -> completion bekerja normal */
    public function test_zero_initial_member_loyalty_works_normally()
    {
        $this->assertEquals(0, $this->member->points);
        $this->assertEquals(0, $this->member->total_pengeluaran);

        $order = $this->createOrder(10000, 0);
        $order->applyMemberLoyaltyOnCompletion();

        $this->assertEquals(1, $this->member->fresh()->points);
        $this->assertEquals(10000, $this->member->fresh()->total_pengeluaran);
    }

    /** 17. final spending <= 0: earnedPoints = 0, total_pengeluaran tidak boleh negatif */
    public function test_final_spending_zero_or_negative_gives_zero_points_and_no_negative_spending()
    {
        $order = $this->createOrder(5000, 10000); // 5000 - 10000 = -5000 -> final spending 0

        $order->applyMemberLoyaltyOnCompletion();

        $this->assertEquals(0, $this->member->fresh()->points);
        $this->assertEquals(0, $this->member->fresh()->total_pengeluaran);
        $this->assertGreaterThanOrEqual(0, $this->member->fresh()->total_pengeluaran);
    }

    /** 18. member_id diset tetapi row Member sudah dihapus: tidak koruptif / error */
    public function test_member_id_set_but_member_record_deleted_does_not_corrupt_completion()
    {
        $order = $this->createOrder(20000, 0, $this->member->id);

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        $this->member->delete();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $order->applyMemberLoyaltyOnCompletion();

        $this->assertEquals('diproses', $order->status);
    }
}
