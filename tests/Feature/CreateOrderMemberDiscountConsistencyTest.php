<?php

namespace Tests\Feature;

use App\Livewire\Order\CreateOrder;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Member;
use App\Models\Menu;
use App\Models\PaymentMethod;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\PriceTier;
use App\Models\SalesChannel;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CreateOrderMemberDiscountConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;
    protected Branch $branchB;
    protected User $kasirA;
    protected PaymentMethod $paymentMethod;
    protected SalesChannel $salesChannel;
    protected PriceTier $priceTier;
    protected Menu $menu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->priceTier = PriceTier::first() ?? PriceTier::create(['nama_tier' => 'Regular', 'is_active' => true]);
        $this->salesChannel = SalesChannel::first() ?? SalesChannel::create(['nama_channel' => 'Dine In', 'is_active' => true]);
        $this->paymentMethod = PaymentMethod::first() ?? PaymentMethod::create([
            'nama_metode' => 'Cash',
            'kode_metode' => 'tunai',
            'is_active' => true,
        ]);

        $this->branchA = Branch::create([
            'nama_cabang' => 'Cabang A',
            'kode_cabang' => 'CBA',
            'is_active' => true,
            'price_tier_id' => $this->priceTier->id,
        ]);

        $this->branchB = Branch::create([
            'nama_cabang' => 'Cabang B',
            'kode_cabang' => 'CBB',
            'is_active' => true,
            'price_tier_id' => $this->priceTier->id,
        ]);

        $this->kasirA = User::factory()->create(['branch_id' => $this->branchA->id]);
        $this->kasirA->assignRole('kasir');

        $category = Category::create(['nama' => 'Minuman']);
        $this->menu = Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Kopi Susu',
            'harga' => 20000,
            'h_pokok' => 8000,
            'is_active' => true,
        ]);
    }

    private function cartPayload(): array
    {
        return [
            (string) $this->menu->id => [
                'id' => $this->menu->id,
                'nama_menu' => $this->menu->nama_menu,
                'harga' => $this->menu->harga,
                'gambar' => '',
                'qty' => 1,
                'selected_options' => [],
                'catatan' => '',
            ],
        ];
    }

    private function createMember(string $phone = '081234567890', string $name = 'Budi Member'): Member
    {
        $user = User::factory()->create(['name' => $name]);

        return Member::create([
            'user_id' => $user->id,
            'phone' => $phone,
            'points' => 10,
            'total_pengeluaran' => 50000,
        ]);
    }

    private function createHistoricalOrder(?Discount $discount, int $discountValue = 1000, ?int $branchId = null): Pesanan
    {
        $pesanan = new Pesanan([
            'kode' => 'HIST-' . Str::random(8),
            'discount_id' => $discount?->id,
            'discount_value' => $discountValue,
            'status' => 'diproses',
            'total' => 20000,
            'total_profit' => 12000,
        ]);
        $pesanan->branch_id = $branchId ?? $this->branchA->id;
        $pesanan->save();

        return $pesanan;
    }

    private function createDiscount(array $attributes = []): Discount
    {
        return Discount::create(array_merge([
            'nama_diskon' => 'KHUSUS MEMBER',
            'type' => 'general',
            'kode_diskon' => 'MEMBER',
            'jenis_diskon' => 'persentase',
            'nilai_diskon' => 5,
            'member_only' => true,
            'scope' => 'global',
            'limit' => 20,
            'digunakan' => null,
            'is_active' => true,
            'tanggal_mulai' => now()->subDay()->toDateString(),
            'tanggal_akhir' => now()->addDay()->toDateString(),
        ], $attributes));
    }

    /**
     * Test 1 — Real UI Flow Under Limit
     * Discount MEMBER global 5%, member_only, limit=20, digunakan=NULL.
     * 19 historical orders exist.
     * User enters member phone and discount code "MEMBER" (no manual set discountId).
     * Assert preview applies 5% and submit persists discount, with usage becoming 20.
     */
    public function test_real_ui_flow_under_limit(): void
    {
        $discount = $this->createDiscount();
        $member = $this->createMember('081234567890', 'Budi Member');

        // 19 historical orders
        for ($i = 0; $i < 19; $i++) {
            $this->createHistoricalOrder($discount, 1000);
        }

        $this->assertEquals(19, $discount->reconciledUsage());

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Pelanggan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('member', $member->phone)
            ->set('discount', 'MEMBER')
            ->call('$refresh');

        // Assert Preview
        $this->assertTrue($component->viewData('isMember'));
        $this->assertEquals(1000, $component->viewData('discountValue'));
        $this->assertEquals(19000, $component->viewData('totalAfterDiscount'));
        $this->assertEquals('Diskon berhasil diterapkan.', $component->viewData('discMessage'));
        $component->assertSee('Voucher Aktif: MEMBER');

        // Call saveOrder
        $component->call('saveOrder');

        // Assert Database
        $order = Pesanan::latest('id')->firstOrFail();
        $this->assertEquals($member->id, $order->member_id);
        $this->assertEquals($discount->id, $order->discount_id);
        $this->assertEquals(1000, $order->discount_value);
        $this->assertEquals(20000, $order->total);
        $this->assertEquals(19000, $order->total - $order->discount_value);

        // Usage is reconciled to 20
        $this->assertEquals(20, $discount->fresh()->digunakan);
        $this->assertEquals(20, $discount->fresh()->reconciledUsage());
    }

    /**
     * Test 2 — Legacy NULL Usage at Limit
     * Setup 20 existing active discounted orders with digunakan = NULL.
     * Preview must use reconciliation, show limit reached message, 0 discountValue, and gross total.
     * UI must not show Voucher Aktif.
     */
    public function test_legacy_null_usage_at_limit(): void
    {
        $discount = $this->createDiscount();
        $member = $this->createMember('081234567890', 'Budi Member');

        // 20 historical orders -> limit reached
        for ($i = 0; $i < 20; $i++) {
            $this->createHistoricalOrder($discount, 1000);
        }

        $this->assertNull($discount->digunakan);
        $this->assertEquals(20, $discount->reconciledUsage());

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Pelanggan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('member', $member->phone)
            ->set('discount', 'MEMBER')
            ->call('$refresh');

        $this->assertEquals('Diskon sudah mencapai batas penggunaan.', $component->viewData('discMessage'));
        $this->assertEquals(0, $component->viewData('discountValue'));
        $this->assertEquals(20000, $component->viewData('totalAfterDiscount'));
        $this->assertNull($component->get('discountId'));
        $component->assertDontSee('Voucher Aktif: MEMBER');
    }

    /**
     * Test 3 — Preview Valid, Limit Habis Sebelum Submit
     * Actual usage = 19. Render preview shows valid discount.
     * Concurrently slot 20 is consumed by another order.
     * Submit user fails explicitly with error toast and full rollback.
     */
    public function test_preview_valid_limit_exhausted_before_submit(): void
    {
        $discount = $this->createDiscount();
        $member = $this->createMember('081234567890', 'Budi Member');

        for ($i = 0; $i < 19; $i++) {
            $this->createHistoricalOrder($discount, 1000);
        }

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Pelanggan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('member', $member->phone)
            ->set('discount', 'MEMBER')
            ->call('$refresh');

        $this->assertEquals(1000, $component->viewData('discountValue'));

        // Concurrently slot 20 is used
        $this->createHistoricalOrder($discount, 1000);
        $this->assertEquals(20, $discount->reconciledUsage());

        $pesananBefore = Pesanan::count();
        $itemBefore = PesananItem::count();

        // Submit now fails explicitly
        $component->call('saveOrder')->assertDispatched('showToast');

        // Order is NOT created, transaction rolled back fully
        $this->assertEquals($pesananBefore, Pesanan::count());
        $this->assertEquals($itemBefore, PesananItem::count());
        $this->assertEquals(20, $discount->fresh()->reconciledUsage());
    }

    /**
     * Test 4 — Normal Member Discount
     * digunakan is numeric under limit. Discount succeeds normally.
     */
    public function test_normal_member_discount(): void
    {
        $discount = $this->createDiscount([
            'limit' => 20,
            'digunakan' => 5,
        ]);

        $member = $this->createMember('081234567890', 'Budi Member');

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Pelanggan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('member', $member->phone)
            ->set('discount', 'MEMBER')
            ->call('$refresh');

        $this->assertEquals(1000, $component->viewData('discountValue'));

        $component->call('saveOrder');

        $order = Pesanan::latest('id')->firstOrFail();
        $this->assertEquals($discount->id, $order->discount_id);
        $this->assertEquals(1000, $order->discount_value);
        $this->assertEquals(6, $discount->fresh()->digunakan);
    }

    /**
     * Test 5 — Invalid Member
     * Member-only discount + invalid phone:
     * preview does not apply, submit rejects explicitly without silent drop.
     */
    public function test_invalid_member(): void
    {
        $discount = $this->createDiscount([
            'digunakan' => 0,
        ]);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Pelanggan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('member', '089999999999') // Non-existent member
            ->set('discount', 'MEMBER')
            ->call('$refresh');

        $this->assertEquals('Diskon ini khusus member. Masukkan nomor member yang valid.', $component->viewData('discMessage'));
        $this->assertEquals(0, $component->viewData('discountValue'));
        $this->assertNull($component->get('discountId'));
        $component->assertDontSee('Voucher Aktif: MEMBER');

        $pesananBefore = Pesanan::count();

        // Submit while discount is still chosen rejects explicitly
        $component->call('saveOrder')->assertDispatched('showToast');

        $this->assertEquals($pesananBefore, Pesanan::count());
    }

    /**
     * Test 6 — Percentage and Maximum
     * 5% of 20000 is 1000, but capped at maksimum_diskon 500.
     * Preview and persisted values match identically.
     */
    public function test_percentage_and_maximum(): void
    {
        $discount = $this->createDiscount([
            'nama_diskon' => 'DISC MAX',
            'kode_diskon' => 'MAX500',
            'jenis_diskon' => 'persentase',
            'nilai_diskon' => 5,
            'maksimum_diskon' => 500,
            'member_only' => false,
            'digunakan' => 0,
        ]);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Pelanggan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('discount', 'MAX500')
            ->call('$refresh');

        $this->assertEquals(500, $component->viewData('discountValue'));
        $this->assertEquals(19500, $component->viewData('totalAfterDiscount'));

        $component->call('saveOrder');

        $order = Pesanan::latest('id')->firstOrFail();
        $this->assertEquals($discount->id, $order->discount_id);
        $this->assertEquals(500, $order->discount_value);
        $this->assertEquals(20000, $order->total);
        $this->assertEquals(19500, $order->total - $order->discount_value);
    }

    /**
     * Test 7 — Nominal Global Discount
     * Nominal discount applies identically across preview and persisted.
     */
    public function test_nominal_global_discount(): void
    {
        $discount = $this->createDiscount([
            'nama_diskon' => 'POTONGAN 5K',
            'kode_diskon' => 'POT5K',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'member_only' => false,
            'digunakan' => 0,
        ]);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Pelanggan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('discount', 'POT5K')
            ->call('$refresh');

        $this->assertEquals(5000, $component->viewData('discountValue'));
        $this->assertEquals(15000, $component->viewData('totalAfterDiscount'));

        $component->call('saveOrder');

        $order = Pesanan::latest('id')->firstOrFail();
        $this->assertEquals($discount->id, $order->discount_id);
        $this->assertEquals(5000, $order->discount_value);
        $this->assertEquals(20000, $order->total);
        $this->assertEquals(15000, $order->total - $order->discount_value);
    }

    /**
     * Test 8 — Branch-specific Discount
     * Existing branch isolation is preserved: Kasir A cannot use Branch B discount.
     */
    public function test_branch_specific_discount(): void
    {
        $discB = $this->createDiscount([
            'nama_diskon' => 'CABANG B ONLY',
            'kode_diskon' => 'BRANCH-B',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'member_only' => false,
            'branch_id' => $this->branchB->id,
            'digunakan' => 0,
        ]);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Pelanggan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('discount', 'BRANCH-B')
            ->call('$refresh');

        // Preview rejects
        $this->assertEquals(0, $component->viewData('discountValue'));
        $this->assertNull($component->viewData('disc'));
        $this->assertNull($component->get('discountId'));

        $pesananBefore = Pesanan::count();

        // Submit rejects explicitly
        $component->call('saveOrder')->assertDispatched('showToast');

        $this->assertEquals($pesananBefore, Pesanan::count());
    }

    /**
     * Test 9 — Private Discount + Member Bypass
     * Valid member can bypass private-discount authorization.
     */
    public function test_private_discount_member_bypass(): void
    {
        $discount = $this->createDiscount([
            'nama_diskon' => 'PRIVATE DISCOUNT',
            'kode_diskon' => 'VIPONLY',
            'type' => 'private',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'member_only' => false,
            'digunakan' => 0,
        ]);

        $member = $this->createMember('081234567890', 'Budi Member');

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Pelanggan')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('member', $member->phone)
            ->set('discount', 'VIPONLY')
            ->call('$refresh');

        // Member bypasses private authorization
        $this->assertEquals(5000, $component->viewData('discountValue'));
        $this->assertEquals('Diskon berhasil diterapkan.', $component->viewData('discMessage'));

        $component->call('saveOrder');

        $order = Pesanan::latest('id')->firstOrFail();
        $this->assertEquals($discount->id, $order->discount_id);
        $this->assertEquals(5000, $order->discount_value);
    }

    /**
     * Test 10 — No Discount
     * Regular order without discount works properly.
     */
    public function test_no_discount(): void
    {
        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Pelanggan Biasa')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('discount', '')
            ->call('$refresh');

        $this->assertEquals(0, $component->viewData('discountValue'));
        $this->assertEquals(20000, $component->viewData('totalAfterDiscount'));

        $component->call('saveOrder');

        $order = Pesanan::latest('id')->firstOrFail();
        $this->assertNull($order->discount_id);
        $this->assertEquals(0, $order->discount_value);
        $this->assertEquals(20000, $order->total);
    }
}
