<?php

namespace Tests\Feature;

use App\Livewire\Order\CreateOrder;
use App\Livewire\Order\TableOrder;
use App\Livewire\Transaksi\Transaksi;
use App\Models\Branch;
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
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class OrderBranchInventoryIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;
    protected Branch $branchB;
    protected User $userA;
    protected User $userB;
    protected User $superadmin;
    protected PriceTier $priceTier;
    protected SalesChannel $salesChannel;
    protected PaymentMethod $paymentMethod;
    protected SatuanBahan $satuan;
    protected Ingredients $ingredientA;
    protected Ingredients $ingredientB;
    protected Menu $menuA;
    protected Menu $menuB;
    protected Member $memberA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->priceTier = PriceTier::first() ?? PriceTier::create(['nama_tier' => 'Regular', 'is_active' => true]);
        $this->salesChannel = SalesChannel::first() ?? SalesChannel::create(['nama_channel' => 'Dine In', 'is_active' => true]);
        $this->paymentMethod = PaymentMethod::first() ?? PaymentMethod::create(['nama_metode' => 'Cash', 'kode_metode' => 'tunai', 'is_active' => true]);

        $this->branchA = Branch::create([
            'nama_cabang' => 'Cabang A',
            'kode_cabang' => 'CBA',
            'price_tier_id' => $this->priceTier->id,
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'nama_cabang' => 'Cabang B',
            'kode_cabang' => 'CBB',
            'price_tier_id' => $this->priceTier->id,
            'is_active' => true,
        ]);

        $this->userA = User::factory()->create(['branch_id' => $this->branchA->id]);
        $this->userA->assignRole('kasir');

        $this->userB = User::factory()->create(['branch_id' => $this->branchB->id]);
        $this->userB->assignRole('kasir');

        $this->superadmin = User::factory()->create(['branch_id' => null]);
        $this->superadmin->assignRole('superadmin');

        $this->satuan = SatuanBahan::create(['nama_satuan' => 'Gram']);

        $this->ingredientA = Ingredients::create([
            'branch_id' => $this->branchA->id,
            'nama_bahan' => 'Biji Kopi A',
            'satuan_id' => $this->satuan->id,
            'stok' => 100,
            'hpp' => 1000,
        ]);

        $this->ingredientB = Ingredients::create([
            'branch_id' => $this->branchB->id,
            'nama_bahan' => 'Biji Kopi B',
            'satuan_id' => $this->satuan->id,
            'stok' => 100,
            'hpp' => 1000,
        ]);

        $category = Category::create(['nama' => 'Minuman']);

        $this->menuA = Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Kopi Branch A',
            'harga' => 20000,
            'h_pokok' => 10000,
            'is_active' => true,
        ]);

        MenuIngredients::create([
            'menu_id' => $this->menuA->id,
            'ingredient_id' => $this->ingredientA->id,
            'qty' => 10,
        ]);

        $this->menuB = Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Kopi Branch B',
            'harga' => 20000,
            'h_pokok' => 10000,
            'is_active' => true,
        ]);

        MenuIngredients::create([
            'menu_id' => $this->menuB->id,
            'ingredient_id' => $this->ingredientB->id,
            'qty' => 10,
        ]);

        $this->memberA = Member::create([
            'user_id' => $this->userA->id,
            'phone' => '081234567890',
            'points' => 0,
            'total_pengeluaran' => 0,
        ]);
    }

    private function createOrderForBranch(Branch $branch, Menu $menu, int $qty = 1, ?User $user = null): Pesanan
    {
        $actor = $user ?? $this->userA;
        $pesanan = Pesanan::create([
            'branch_id' => $branch->id,
            'kode' => 'ORD-' . strtoupper(uniqid()),
            'nama' => 'Pelanggan ' . $branch->nama_cabang,
            'user_id' => $actor->id,
            'member_id' => $this->memberA->id,
            'payment_method_id' => $this->paymentMethod->id,
            'sales_channel_id' => $this->salesChannel->id,
            'total' => $menu->harga * $qty,
            'total_profit' => ($menu->harga - $menu->h_pokok) * $qty,
            'status' => 'diproses',
        ]);

        PesananItem::create([
            'pesanans_id' => $pesanan->id,
            'menus_id' => $menu->id,
            'qty' => $qty,
            'harga_satuan' => $menu->harga,
            'subtotal' => $menu->harga * $qty,
            'discount_value' => 0,
            'profit' => ($menu->harga - $menu->h_pokok) * $qty,
        ]);

        return $pesanan;
    }

    /** 1. Order Branch A + Ingredient Branch A -> deduction berhasil */
    public function test_order_branch_a_with_ingredient_branch_a_deduction_succeeds()
    {
        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 2);

        $order->processInventoryDeduction();

        $this->assertEquals(80, $this->ingredientA->fresh()->stok);
    }

    /** 2. RiwayatStock hasil deduction -> branch_id = Branch A */
    public function test_riwayat_stock_branch_id_matches_order_branch()
    {
        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 1);

        $order->processInventoryDeduction();

        $history = RiwayatStock::where('ingredient_id', $this->ingredientA->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->branchA->id, $history->branch_id);
        $this->assertEquals('out', $history->tipe);
        $this->assertEquals(10, $history->qty);
    }

    /** 3. Superadmin branch NULL menyelesaikan Order Branch A -> stok Branch A berkurang -> history Branch A -> order selesai */
    public function test_superadmin_null_branch_completes_order_branch_a()
    {
        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 1, $this->userA);

        Livewire::actingAs($this->superadmin)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus')
            ->assertDispatched('showToast', type: 'success');

        $this->assertEquals('selesai', $order->fresh()->status);
        $this->assertEquals(90, $this->ingredientA->fresh()->stok);

        $history = RiwayatStock::where('ingredient_id', $this->ingredientA->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->branchA->id, $history->branch_id);
    }

    /** 4. Authenticated user Branch B + Order Branch A -> model deduction tetap memakai Ingredient Branch A, bukan Branch B */
    public function test_authenticated_user_branch_b_deducts_order_branch_a_ingredients()
    {
        $this->actingAs($this->userB);

        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 1, $this->userA);

        $order->processInventoryDeduction();

        $this->assertEquals(90, $this->ingredientA->fresh()->stok);
        $this->assertEquals(100, $this->ingredientB->fresh()->stok);

        // User B from Branch B cannot see it via scoped query
        $this->assertNull(RiwayatStock::where('ingredient_id', $this->ingredientA->id)->first());

        // Without global scope, verify record exists and strictly belongs to Branch A
        $history = RiwayatStock::withoutGlobalScope('branch_filter')->where('ingredient_id', $this->ingredientA->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->branchA->id, $history->branch_id);
        $this->assertNotEquals($this->userB->branch_id, $history->branch_id);
    }

    /** 5. Order Branch A + recipe hanya menunjuk Ingredient Branch B -> gagal -> Ingredient B tidak berubah -> history 0 */
    public function test_order_branch_a_with_recipe_branch_b_fails_and_leaves_stock_untouched()
    {
        $order = $this->createOrderForBranch($this->branchA, $this->menuB, 1, $this->userA);

        $stockBBefore = $this->ingredientB->fresh()->stok;
        $historyCountBefore = RiwayatStock::count();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Bahan baku dengan ID {$this->ingredientB->id} tidak ditemukan.");

        try {
            DB::transaction(function () use ($order) {
                $order->processInventoryDeduction();
            });
        } finally {
            $this->assertEquals($stockBBefore, $this->ingredientB->fresh()->stok);
            $this->assertEquals($historyCountBefore, RiwayatStock::count());
        }
    }

    /** 6. Order Branch A + recipe campuran: Ingredient Branch A + Ingredient Branch B -> seluruh operation gagal -> Ingredient A tidak ikut terpotong -> Ingredient B tidak berubah -> history 0 */
    public function test_order_branch_a_with_mixed_recipe_fails_completely_with_zero_deduction()
    {
        // Add mixed recipe: menu with both ingredientA (Branch A) and ingredientB (Branch B)
        $mixedMenu = Menu::create([
            'categories_id' => $this->menuA->categories_id,
            'nama_menu' => 'Kopi Campuran Dua Cabang',
            'harga' => 30000,
            'h_pokok' => 15000,
            'is_active' => true,
        ]);

        MenuIngredients::create([
            'menu_id' => $mixedMenu->id,
            'ingredient_id' => $this->ingredientA->id,
            'qty' => 10,
        ]);
        MenuIngredients::create([
            'menu_id' => $mixedMenu->id,
            'ingredient_id' => $this->ingredientB->id,
            'qty' => 5,
        ]);

        $order = $this->createOrderForBranch($this->branchA, $mixedMenu, 1, $this->userA);

        $stockABefore = $this->ingredientA->fresh()->stok;
        $stockBBefore = $this->ingredientB->fresh()->stok;
        $historyCountBefore = RiwayatStock::count();

        $failed = false;
        try {
            DB::transaction(function () use ($order) {
                $order->processInventoryDeduction();
            });
        } catch (\Exception $e) {
            $failed = true;
            $this->assertStringContainsString("Bahan baku dengan ID {$this->ingredientB->id} tidak ditemukan.", $e->getMessage());
        }

        $this->assertTrue($failed, 'Mixed recipe should throw exception');
        $this->assertEquals($stockABefore, $this->ingredientA->fresh()->stok, 'Ingredient A must NOT be partially deducted');
        $this->assertEquals($stockBBefore, $this->ingredientB->fresh()->stok, 'Ingredient B must remain unchanged');
        $this->assertEquals($historyCountBefore, RiwayatStock::count(), 'Zero history records must be created');
    }

    /** 7. Variant ingredient Branch B pada Order Branch A -> completion gagal dan rollback */
    public function test_variant_ingredient_branch_b_on_order_branch_a_fails_and_rollbacks()
    {
        $vg = VariantGroup::create([
            'nama_group' => 'Extra',
            'selection_type' => 'single',
            'is_required' => false,
        ]);
        $this->menuA->variantGroups()->attach($vg->id);

        $variantOptBranchB = VariantOption::create([
            'variant_group_id' => $vg->id,
            'nama_opsi' => 'Addon Branch B',
            'extra_price' => 3000,
        ]);
        $variantOptBranchB->ingredients()->attach($this->ingredientB->id, ['qty' => 5]);

        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 1, $this->userA);
        $order->items->first()->variants()->attach($variantOptBranchB->id);

        $stockABefore = $this->ingredientA->fresh()->stok;
        $stockBBefore = $this->ingredientB->fresh()->stok;
        $historyCountBefore = RiwayatStock::count();

        $failed = false;
        try {
            DB::transaction(function () use ($order) {
                $order->processInventoryDeduction();
            });
        } catch (\Exception $e) {
            $failed = true;
            $this->assertStringContainsString("Bahan baku dengan ID {$this->ingredientB->id} tidak ditemukan.", $e->getMessage());
        }

        $this->assertTrue($failed, 'Variant ingredient from branch B should fail completion');
        $this->assertEquals($stockABefore, $this->ingredientA->fresh()->stok);
        $this->assertEquals($stockBBefore, $this->ingredientB->fresh()->stok);
        $this->assertEquals($historyCountBefore, RiwayatStock::count());
    }

    /** 8. Order branch NULL + recipe -> fail-safe -> tidak ada deduction/history */
    public function test_order_branch_null_with_recipe_fails_safely()
    {
        $order = Pesanan::create([
            'branch_id' => null,
            'kode' => 'ORD-NULL-BRANCH',
            'nama' => 'Pelanggan Null Branch',
            'user_id' => $this->userA->id,
            'member_id' => $this->memberA->id,
            'payment_method_id' => $this->paymentMethod->id,
            'sales_channel_id' => $this->salesChannel->id,
            'total' => 20000,
            'total_profit' => 10000,
            'status' => 'diproses',
        ]);

        PesananItem::create([
            'pesanans_id' => $order->id,
            'menus_id' => $this->menuA->id,
            'qty' => 1,
            'harga_satuan' => 20000,
            'subtotal' => 20000,
            'discount_value' => 0,
            'profit' => 10000,
        ]);

        $stockABefore = $this->ingredientA->fresh()->stok;
        $historyCountBefore = RiwayatStock::count();

        $failed = false;
        try {
            DB::transaction(function () use ($order) {
                $order->processInventoryDeduction();
            });
        } catch (\Exception $e) {
            $failed = true;
            $this->assertEquals('Pesanan tidak memiliki cabang yang valid untuk pemrosesan inventory.', $e->getMessage());
        }

        $this->assertTrue($failed, 'Order with branch null must throw fail-safe exception');
        $this->assertEquals($stockABefore, $this->ingredientA->fresh()->stok);
        $this->assertEquals($historyCountBefore, RiwayatStock::count());
        $this->assertEquals('diproses', $order->fresh()->status);
    }

    /** 8B. Order branch NULL tanpa recipe -> return cleanly tanpa exception */
    public function test_order_branch_null_without_recipe_returns_without_deduction()
    {
        $order = Pesanan::create([
            'branch_id' => null,
            'kode' => 'ORD-NO-RECIPE',
            'nama' => 'Pelanggan No Recipe',
            'user_id' => $this->userA->id,
            'member_id' => $this->memberA->id,
            'payment_method_id' => $this->paymentMethod->id,
            'sales_channel_id' => $this->salesChannel->id,
            'total' => 20000,
            'total_profit' => 10000,
            'status' => 'diproses',
        ]);

        // No items / recipes attached
        $order->processInventoryDeduction();

        $this->assertEquals(0, RiwayatStock::count());
        $this->assertEquals('diproses', $order->fresh()->status);
    }

    /** 9. Soft-deleted Ingredient Branch A -> tetap dianggap missing -> rollback */
    public function test_soft_deleted_ingredient_branch_a_is_treated_as_missing_and_rollbacks()
    {
        $this->ingredientA->delete();
        $this->assertSoftDeleted('ingredients', ['id' => $this->ingredientA->id]);

        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 1, $this->userA);

        $failed = false;
        try {
            DB::transaction(function () use ($order) {
                $order->processInventoryDeduction();
            });
        } catch (\Exception $e) {
            $failed = true;
            $this->assertStringContainsString("Bahan baku dengan ID {$this->ingredientA->id} tidak ditemukan.", $e->getMessage());
        }

        $this->assertTrue($failed, 'Soft-deleted ingredient must trigger missing ingredient exception');
        $this->assertEquals(0, RiwayatStock::count());
    }

    /** 10. Negative stock Branch A -> tetap diperbolehkan */
    public function test_negative_stock_is_allowed_without_blocking()
    {
        $this->ingredientA->update(['stok' => 2]);

        // Needs 10 (2 - 10 = -8)
        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 1, $this->userA);

        $order->processInventoryDeduction();

        $this->assertEquals(-8, $this->ingredientA->fresh()->stok);

        $history = RiwayatStock::where('ingredient_id', $this->ingredientA->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals(2, $history->qty_before);
        $this->assertEquals(10, $history->qty);
        $this->assertEquals(-8, $history->qty_after);
    }

    /** 11. qty_before / qty / qty_after tetap benar */
    public function test_qty_before_qty_and_qty_after_are_accurate()
    {
        $this->ingredientA->update(['stok' => 55]);

        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 2, $this->userA); // 2 * 10 = 20

        $order->processInventoryDeduction();

        $history = RiwayatStock::where('ingredient_id', $this->ingredientA->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals(55, $history->qty_before);
        $this->assertEquals(20, $history->qty);
        $this->assertEquals(35, $history->qty_after);
        $this->assertEquals($this->branchA->id, $history->branch_id);
    }

    /** 12. Satu ingredient yang muncul pada base + variant -> tetap diagregasi menjadi satu history */
    public function test_same_ingredient_in_base_and_variant_aggregated_into_single_history()
    {
        $vg = VariantGroup::create([
            'nama_group' => 'Extra Kopi',
            'selection_type' => 'single',
            'is_required' => false,
        ]);
        $this->menuA->variantGroups()->attach($vg->id);

        $variantOpt = VariantOption::create([
            'variant_group_id' => $vg->id,
            'nama_opsi' => 'Double Shot',
            'extra_price' => 5000,
        ]);
        // Same ingredientA used in variant!
        $variantOpt->ingredients()->attach($this->ingredientA->id, ['qty' => 5]);

        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 2, $this->userA); // base: 2 * 10 = 20
        $order->items->first()->variants()->attach($variantOpt->id); // variant: 2 * 5 = 10 -> total = 30

        $order->processInventoryDeduction();

        $this->assertEquals(70, $this->ingredientA->fresh()->stok);

        $histories = RiwayatStock::where('ingredient_id', $this->ingredientA->id)->get();
        $this->assertCount(1, $histories, 'Should create exactly 1 consolidated history row');
        $this->assertEquals(100, $histories->first()->qty_before);
        $this->assertEquals(30, $histories->first()->qty);
        $this->assertEquals(70, $histories->first()->qty_after);
        $this->assertEquals($this->branchA->id, $histories->first()->branch_id);
    }

    /** 13. Repeated completion -> tetap tidak double deduct */
    public function test_repeated_completion_does_not_double_deduct()
    {
        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 1, $this->userA);

        $component = Livewire::actingAs($this->userA)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus');

        $this->assertEquals(90, $this->ingredientA->fresh()->stok);
        $this->assertEquals(1, RiwayatStock::count());

        // Second call
        $component->call('updateStatus');

        $this->assertEquals(90, $this->ingredientA->fresh()->stok);
        $this->assertEquals(1, RiwayatStock::count());
    }

    /** 14. History branch tidak pernah mengikuti actor branch ketika actor berbeda dari order branch */
    public function test_history_branch_never_follows_actor_branch_when_actor_differs()
    {
        // Actor is userB from Branch B, but Order is Branch A
        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 1, $this->userA);

        $this->actingAs($this->userB);
        $order->processInventoryDeduction();

        // User B from Branch B cannot see it via scoped query
        $this->assertNull(RiwayatStock::where('ingredient_id', $this->ingredientA->id)->first());

        // Without global scope, verify record exists and strictly belongs to Branch A
        $history = RiwayatStock::withoutGlobalScope('branch_filter')->where('ingredient_id', $this->ingredientA->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->branchA->id, $history->branch_id);
        $this->assertNotEquals($this->userB->branch_id, $history->branch_id);
    }

    /** Path 1: TableOrder::saji() uses new model branch isolation */
    public function test_table_order_saji_path_enforces_order_branch()
    {
        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 1, $this->userA);

        Livewire::actingAs($this->userA)
            ->test(TableOrder::class)
            ->call('saji', base64_encode((string) $order->id))
            ->assertDispatched('showToast', type: 'success');

        $this->assertEquals('selesai', $order->fresh()->status);
        $this->assertEquals(90, $this->ingredientA->fresh()->stok);

        $history = RiwayatStock::where('ingredient_id', $this->ingredientA->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->branchA->id, $history->branch_id);
    }

    /** Path 2: Transaksi::updateStatus() uses new model branch isolation */
    public function test_transaksi_update_status_path_enforces_order_branch()
    {
        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 1, $this->userA);

        Livewire::actingAs($this->userA)
            ->test(Transaksi::class)
            ->set('selectedOrder', $order)
            ->set('status', 'selesai')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->call('updateStatus')
            ->assertDispatched('showToast', type: 'success');

        $this->assertEquals('selesai', $order->fresh()->status);
        $this->assertEquals(90, $this->ingredientA->fresh()->stok);

        $history = RiwayatStock::where('ingredient_id', $this->ingredientA->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->branchA->id, $history->branch_id);
    }

    /** Path 3: CreateOrder::completeLastOrder() uses new model branch isolation */
    public function test_create_order_complete_last_order_path_enforces_order_branch()
    {
        $order = $this->createOrderForBranch($this->branchA, $this->menuA, 1, $this->userA);

        Livewire::actingAs($this->userA)
            ->test(CreateOrder::class)
            ->set('lastPesananId', $order->id)
            ->call('completeLastOrder')
            ->assertDispatched('showToast', type: 'success');

        $this->assertEquals('selesai', $order->fresh()->status);
        $this->assertEquals(90, $this->ingredientA->fresh()->stok);

        $history = RiwayatStock::where('ingredient_id', $this->ingredientA->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->branchA->id, $history->branch_id);
    }
}
