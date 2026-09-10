<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Ingredients;
use App\Models\Menu;
use App\Models\MenuPrice;
use App\Models\PriceTier;
use App\Models\SalesChannel;
use App\Models\SatuanBahan;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecipeBranchIntegrityAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Branch $inactiveBranch;
    protected PriceTier $tierA;
    protected PriceTier $tierB;
    protected SalesChannel $salesChannel;
    protected SatuanBahan $satuanKg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->category = Category::create(['nama' => 'Minuman']);

        $this->tierA = PriceTier::create(['nama_tier' => 'Tier A', 'is_active' => true]);
        $this->tierB = PriceTier::create(['nama_tier' => 'Tier B', 'is_active' => true]);

        $this->salesChannel = SalesChannel::first() ?? SalesChannel::create([
            'nama_channel' => 'Dine In',
            'is_active' => true,
        ]);

        $this->branchA = Branch::create([
            'nama_cabang' => 'Cabang Alpha',
            'kode_cabang' => 'ALP',
            'price_tier_id' => $this->tierA->id,
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'nama_cabang' => 'Cabang Beta',
            'kode_cabang' => 'BET',
            'price_tier_id' => $this->tierB->id,
            'is_active' => true,
        ]);

        $this->inactiveBranch = Branch::create([
            'nama_cabang' => 'Cabang Nonaktif',
            'kode_cabang' => 'NOA',
            'price_tier_id' => $this->tierA->id,
            'is_active' => false,
        ]);

        $this->satuanKg = SatuanBahan::create(['nama_satuan' => 'Kg']);
    }

    /**
     * Helper to create an active menu priced at given branches.
     *
     * @param Branch[] $branches
     */
    protected function createSaleableMenu(string $name, array $branches): Menu
    {
        $menu = Menu::create([
            'categories_id' => $this->category->id,
            'nama_menu' => $name,
            'is_active' => true,
        ]);

        foreach ($branches as $branch) {
            if ($branch->price_tier_id) {
                MenuPrice::create([
                    'menu_id' => $menu->id,
                    'price_tier_id' => $branch->price_tier_id,
                    'sales_channel_id' => $this->salesChannel->id,
                    'harga' => 25000,
                ]);
            }
        }

        return $menu;
    }

    /**
     * 1. menu branch A + ingredient A -> OK.
     */
    public function test_menu_branch_a_with_ingredient_a_is_ok(): void
    {
        $menu = $this->createSaleableMenu('Kopi Alpha', [$this->branchA]);

        $ingredientA = Ingredients::create([
            'nama_bahan' => 'Kopi Biji A',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 50,
            'hpp' => 5000,
            'branch_id' => $this->branchA->id,
        ]);

        DB::table('menu_ingredients')->insert([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredientA->id,
            'qty' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Saleable menu/branch pairs     : 1')
            ->expectsOutputToContain('Base recipe rows               : 1')
            ->expectsOutputToContain('Base OK                        : 1')
            ->expectsOutputToContain('Base CROSS_BRANCH              : 0')
            ->expectsOutputToContain('No base recipe integrity issues found.')
            ->assertSuccessful();
    }

    /**
     * 2. menu branch A + ingredient B -> CROSS_BRANCH.
     */
    public function test_menu_branch_a_with_ingredient_b_is_cross_branch(): void
    {
        $menu = $this->createSaleableMenu('Kopi Alpha', [$this->branchA]);

        $ingredientB = Ingredients::create([
            'nama_bahan' => 'Kopi Biji B',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 50,
            'hpp' => 5000,
            'branch_id' => $this->branchB->id,
        ]);

        DB::table('menu_ingredients')->insert([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredientB->id,
            'qty' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Base recipe rows               : 1')
            ->expectsOutputToContain('Base OK                        : 0')
            ->expectsOutputToContain('Base CROSS_BRANCH              : 1')
            ->expectsOutputToContain('CROSS_BRANCH')
            ->assertSuccessful();
    }

    /**
     * 3. menu tersedia A+B dengan ingredient A -> A OK, B CROSS_BRANCH.
     */
    public function test_menu_available_a_and_b_with_ingredient_a_results_in_a_ok_and_b_cross_branch(): void
    {
        $menu = $this->createSaleableMenu('Lemon Tea Multi', [$this->branchA, $this->branchB]);

        $ingredientA = Ingredients::create([
            'nama_bahan' => 'Syrup Lemon Alpha',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 20,
            'hpp' => 10000,
            'branch_id' => $this->branchA->id,
        ]);

        DB::table('menu_ingredients')->insert([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredientA->id,
            'qty' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Saleable menu/branch pairs     : 2')
            ->expectsOutputToContain('Base recipe rows               : 2')
            ->expectsOutputToContain('Base OK                        : 1')
            ->expectsOutputToContain('Base CROSS_BRANCH              : 1')
            ->expectsOutputToContain('Multi-branch recipe risks      : 1')
            ->assertSuccessful();
    }

    /**
     * 4. branch_menu explicit false membuat branch tersebut tidak dihitung saleable.
     */
    public function test_branch_menu_explicit_false_excludes_branch_from_saleable(): void
    {
        $menu = $this->createSaleableMenu('Espresso Exclusive', [$this->branchA, $this->branchB]);

        // Explicitly mark unavailable at Branch B
        DB::table('branch_menu')->insert([
            'branch_id' => $this->branchB->id,
            'menu_id' => $menu->id,
            'is_available' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ingredientA = Ingredients::create([
            'nama_bahan' => 'Espresso Beans A',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 30,
            'hpp' => 8000,
            'branch_id' => $this->branchA->id,
        ]);

        DB::table('menu_ingredients')->insert([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredientA->id,
            'qty' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Because Branch B is unavailable, only Branch A is saleable -> no CROSS_BRANCH
        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Saleable menu/branch pairs     : 1')
            ->expectsOutputToContain('Base recipe rows               : 1')
            ->expectsOutputToContain('Base OK                        : 1')
            ->expectsOutputToContain('Base CROSS_BRANCH              : 0')
            ->assertSuccessful();
    }

    /**
     * 5. tanpa branch_menu row tetap dihitung AVAILABLE.
     */
    public function test_absence_of_branch_menu_row_defaults_to_available(): void
    {
        $menu = $this->createSaleableMenu('Teh Manis Default', [$this->branchA]);

        // Ensure no row exists in branch_menu
        $this->assertDatabaseMissing('branch_menu', [
            'menu_id' => $menu->id,
            'branch_id' => $this->branchA->id,
        ]);

        $ingredientA = Ingredients::create([
            'nama_bahan' => 'Gula Manis A',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 15,
            'hpp' => 3000,
            'branch_id' => $this->branchA->id,
        ]);

        DB::table('menu_ingredients')->insert([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredientA->id,
            'qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Saleable menu/branch pairs     : 1')
            ->expectsOutputToContain('Base OK                        : 1')
            ->assertSuccessful();
    }

    /**
     * 6. menu tanpa harga tier branch tidak dianggap saleable di branch tersebut.
     */
    public function test_menu_without_price_tier_for_branch_is_not_saleable_at_that_branch(): void
    {
        // Menu only priced for Tier A (Branch A), not Tier B (Branch B)
        $menu = $this->createSaleableMenu('Menu Alpha Only', [$this->branchA]);

        $ingredientA = Ingredients::create([
            'nama_bahan' => 'Bahan Alpha Only',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 4000,
            'branch_id' => $this->branchA->id,
        ]);

        DB::table('menu_ingredients')->insert([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredientA->id,
            'qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Saleable menu/branch pairs     : 1')
            ->expectsOutputToContain('Base recipe rows               : 1')
            ->expectsOutputToContain('Base OK                        : 1')
            ->expectsOutputToContain('Base CROSS_BRANCH              : 0')
            ->assertSuccessful();
    }

    /**
     * 7. inactive menu tidak menjadi operational risk.
     */
    public function test_inactive_menu_does_not_become_operational_risk(): void
    {
        $menu = $this->createSaleableMenu('Menu Nonaktif', [$this->branchA]);
        $menu->update(['is_active' => false]);

        $ingredientB = Ingredients::create([
            'nama_bahan' => 'Bahan Mismatch Inactive',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 4000,
            'branch_id' => $this->branchB->id,
        ]);

        DB::table('menu_ingredients')->insert([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredientB->id,
            'qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Active menus                   : 0')
            ->expectsOutputToContain('Saleable menu/branch pairs     : 0')
            ->expectsOutputToContain('Base recipe rows               : 0')
            ->expectsOutputToContain('Base CROSS_BRANCH              : 0')
            ->assertSuccessful();
    }

    /**
     * 8. inactive branch tidak menjadi operational risk.
     */
    public function test_inactive_branch_does_not_become_operational_risk(): void
    {
        $tierC = PriceTier::create(['nama_tier' => 'Tier C', 'is_active' => true]);
        $this->inactiveBranch->update(['price_tier_id' => $tierC->id]);

        // Menu priced only for inactive branch's price tier
        $menu = $this->createSaleableMenu('Menu Cabang Tutup', [$this->inactiveBranch]);

        $ingredientA = Ingredients::create([
            'nama_bahan' => 'Bahan Inactive Branch',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 4000,
            'branch_id' => $this->branchA->id,
        ]);

        DB::table('menu_ingredients')->insert([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredientA->id,
            'qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Inactive branch is excluded from active branches -> 0 saleable pairs
        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Saleable menu/branch pairs     : 0')
            ->expectsOutputToContain('Base recipe rows               : 0')
            ->expectsOutputToContain('Base CROSS_BRANCH              : 0')
            ->assertSuccessful();
    }

    /**
     * 9. ingredient branch NULL -> BRANCHLESS.
     */
    public function test_ingredient_with_null_branch_is_classified_as_branchless(): void
    {
        $menu = $this->createSaleableMenu('Menu Branchless Test', [$this->branchA]);

        $ingredientNull = Ingredients::create([
            'nama_bahan' => 'Bahan Tanpa Cabang',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 4000,
            'branch_id' => null,
        ]);

        DB::table('menu_ingredients')->insert([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredientNull->id,
            'qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Base BRANCHLESS                : 1')
            ->expectsOutputToContain('Base OK                        : 0')
            ->expectsOutputToContain('BRANCHLESS_INGREDIENT')
            ->assertSuccessful();
    }

    /**
     * 10. soft-deleted ingredient -> SOFT_DELETED.
     */
    public function test_soft_deleted_ingredient_is_classified_as_soft_deleted(): void
    {
        $menu = $this->createSaleableMenu('Menu Soft Delete Test', [$this->branchA]);

        $ingredient = Ingredients::create([
            'nama_bahan' => 'Bahan Terhapus',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 4000,
            'branch_id' => $this->branchA->id,
        ]);
        $ingredient->delete();

        DB::table('menu_ingredients')->insert([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredient->id,
            'qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Base SOFT_DELETED              : 1')
            ->expectsOutputToContain('Base OK                        : 0')
            ->expectsOutputToContain('SOFT_DELETED_INGREDIENT')
            ->assertSuccessful();
    }

    /**
     * 11. physical missing ingredient bila fixture memungkinkan -> MISSING.
     */
    public function test_physically_missing_ingredient_is_classified_as_missing(): void
    {
        $menu = $this->createSaleableMenu('Menu Missing Test', [$this->branchA]);

        $canCreateFixture = false;
        try {
            DB::statement('PRAGMA foreign_keys = OFF;');
            DB::table('menu_ingredients')->insert([
                'menu_id' => $menu->id,
                'ingredient_id' => 999999,
                'qty' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $canCreateFixture = true;
        } catch (\Throwable $e) {
            $canCreateFixture = false;
        }

        if ($canCreateFixture) {
            $this->artisan('inventory:audit-recipe-branch-integrity')
                ->expectsOutputToContain('Base MISSING                   : 1')
                ->expectsOutputToContain('Base OK                        : 0')
                ->expectsOutputToContain('MISSING_INGREDIENT')
                ->assertSuccessful();
        } else {
            // Bila fixture tidak memungkinkan karena FK constraint database (SQLite transaction),
            // verifikasi bahwa klasifikasi mengenali missing ingredient (null) secara deterministik.
            $command = new \App\Console\Commands\AuditRecipeBranchIntegrityCommand();
            $reflection = new \ReflectionMethod($command, 'classifyIngredientStatus');
            $status = $reflection->invoke($command, null, $this->branchA->id);
            $this->assertSame('MISSING_INGREDIENT', $status);
        }
    }

    /**
     * 12. variant ingredient same branch -> OK.
     */
    public function test_variant_ingredient_same_branch_is_ok(): void
    {
        $menu = $this->createSaleableMenu('Kopi Susu Varian', [$this->branchA]);

        $vg = VariantGroup::create(['nama_group' => 'Ukuran']);
        $vo = VariantOption::create([
            'variant_group_id' => $vg->id,
            'nama_opsi' => 'Large',
        ]);
        DB::table('menu_variant_group')->insert([
            'menu_id' => $menu->id,
            'variant_group_id' => $vg->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ingredientA = Ingredients::create([
            'nama_bahan' => 'Susu Segar Alpha',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 40,
            'hpp' => 12000,
            'branch_id' => $this->branchA->id,
        ]);

        DB::table('variant_option_ingredients')->insert([
            'variant_option_id' => $vo->id,
            'ingredient_id' => $ingredientA->id,
            'qty' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Variant recipe rows            : 1')
            ->expectsOutputToContain('Variant OK                     : 1')
            ->expectsOutputToContain('Variant CROSS_BRANCH           : 0')
            ->expectsOutputToContain('No variant recipe integrity issues found.')
            ->assertSuccessful();
    }

    /**
     * 13. variant ingredient cross branch -> CROSS_BRANCH.
     */
    public function test_variant_ingredient_cross_branch_is_cross_branch(): void
    {
        $menu = $this->createSaleableMenu('Kopi Susu Varian Mismatch', [$this->branchA]);

        $vg = VariantGroup::create(['nama_group' => 'Ukuran']);
        $vo = VariantOption::create([
            'variant_group_id' => $vg->id,
            'nama_opsi' => 'Large',
        ]);
        DB::table('menu_variant_group')->insert([
            'menu_id' => $menu->id,
            'variant_group_id' => $vg->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ingredientB = Ingredients::create([
            'nama_bahan' => 'Susu Segar Beta',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 40,
            'hpp' => 12000,
            'branch_id' => $this->branchB->id,
        ]);

        DB::table('variant_option_ingredients')->insert([
            'variant_option_id' => $vo->id,
            'ingredient_id' => $ingredientB->id,
            'qty' => 50,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Variant recipe rows            : 1')
            ->expectsOutputToContain('Variant OK                     : 0')
            ->expectsOutputToContain('Variant CROSS_BRANCH           : 1')
            ->expectsOutputToContain('CROSS_BRANCH')
            ->assertSuccessful();
    }

    /**
     * 14. shared VariantGroup pada beberapa menu dievaluasi per menu.
     */
    public function test_shared_variant_group_across_multiple_menus_is_evaluated_per_menu(): void
    {
        $menu1 = $this->createSaleableMenu('Menu Alpha 1', [$this->branchA]);
        $menu2 = $this->createSaleableMenu('Menu Beta 2', [$this->branchB]);

        $vg = VariantGroup::create(['nama_group' => 'Extra Topping']);
        $vo = VariantOption::create([
            'variant_group_id' => $vg->id,
            'nama_opsi' => 'Boba',
        ]);

        // Shared across menu1 and menu2
        DB::table('menu_variant_group')->insert([
            ['menu_id' => $menu1->id, 'variant_group_id' => $vg->id, 'created_at' => now(), 'updated_at' => now()],
            ['menu_id' => $menu2->id, 'variant_group_id' => $vg->id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Boba belongs to Branch A
        $ingredientA = Ingredients::create([
            'nama_bahan' => 'Boba Alpha',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 20,
            'hpp' => 7000,
            'branch_id' => $this->branchA->id,
        ]);

        DB::table('variant_option_ingredients')->insert([
            'variant_option_id' => $vo->id,
            'ingredient_id' => $ingredientA->id,
            'qty' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Evaluated against Menu 1 (Branch A) -> OK (1)
        // Evaluated against Menu 2 (Branch B) -> CROSS_BRANCH (1)
        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Variant recipe rows            : 2')
            ->expectsOutputToContain('Variant OK                     : 1')
            ->expectsOutputToContain('Variant CROSS_BRANCH           : 1')
            ->assertSuccessful();
    }

    /**
     * 15. duplicate branch_menu dilaporkan diagnostic.
     */
    public function test_duplicate_branch_menu_reported_diagnostically(): void
    {
        $menu = $this->createSaleableMenu('Menu Duplicate Test', [$this->branchA]);

        // Insert duplicate rows for same branch_id and menu_id
        DB::table('branch_menu')->insert([
            ['branch_id' => $this->branchA->id, 'menu_id' => $menu->id, 'is_available' => true, 'created_at' => now(), 'updated_at' => now()],
            ['branch_id' => $this->branchA->id, 'menu_id' => $menu->id, 'is_available' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Branch menu duplicates         : 1')
            ->expectsOutputToContain('DIAGNOSTIC: BRANCH MENU DUPLICATE ROWS:')
            ->assertSuccessful();
    }

    /**
     * 16. command menghasilkan zero database mutation.
     */
    public function test_command_produces_zero_database_mutations(): void
    {
        $menu = $this->createSaleableMenu('Zero Mutation Menu', [$this->branchA, $this->branchB]);

        $ingredient = Ingredients::create([
            'nama_bahan' => 'Bahan Zero Mutation',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 50,
            'hpp' => 10000,
            'branch_id' => $this->branchA->id,
        ]);

        DB::table('menu_ingredients')->insert([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredient->id,
            'qty' => 5,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $vg = VariantGroup::create(['nama_group' => 'Grup Test']);
        $vo = VariantOption::create(['variant_group_id' => $vg->id, 'nama_opsi' => 'Opsi Test']);
        DB::table('menu_variant_group')->insert([
            'menu_id' => $menu->id,
            'variant_group_id' => $vg->id,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        DB::table('variant_option_ingredients')->insert([
            'variant_option_id' => $vo->id,
            'ingredient_id' => $ingredient->id,
            'qty' => 2,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        // Snapshot tables before
        $tables = [
            'ingredients' => DB::table('ingredients')->get()->toArray(),
            'menu_ingredients' => DB::table('menu_ingredients')->get()->toArray(),
            'variant_option_ingredients' => DB::table('variant_option_ingredients')->get()->toArray(),
            'branch_menu' => DB::table('branch_menu')->get()->toArray(),
            'menus' => DB::table('menus')->get()->toArray(),
            'branches' => DB::table('branches')->get()->toArray(),
        ];

        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Database changes               : 0')
            ->assertSuccessful();

        // Snapshot tables after
        $afterTables = [
            'ingredients' => DB::table('ingredients')->get()->toArray(),
            'menu_ingredients' => DB::table('menu_ingredients')->get()->toArray(),
            'variant_option_ingredients' => DB::table('variant_option_ingredients')->get()->toArray(),
            'branch_menu' => DB::table('branch_menu')->get()->toArray(),
            'menus' => DB::table('menus')->get()->toArray(),
            'branches' => DB::table('branches')->get()->toArray(),
        ];

        $this->assertEquals($tables, $afterTables, 'Database content must remain completely unmodified after audit command.');
    }

    /**
     * 17. negative stock tidak relevan terhadap audit (stok = -10 tetap OK).
     */
    public function test_negative_stock_does_not_affect_ownership_audit_status(): void
    {
        $menu = $this->createSaleableMenu('Menu Negative Stock', [$this->branchA]);

        $ingredientNegative = Ingredients::create([
            'nama_bahan' => 'Bahan Stok Minus',
            'satuan_id' => $this->satuanKg->id,
            'stok' => -10,
            'hpp' => 5000,
            'branch_id' => $this->branchA->id,
        ]);

        DB::table('menu_ingredients')->insert([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredientNegative->id,
            'qty' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('inventory:audit-recipe-branch-integrity')
            ->expectsOutputToContain('Base OK                        : 1')
            ->expectsOutputToContain('Base CROSS_BRANCH              : 0')
            ->expectsOutputToContain('No base recipe integrity issues found.')
            ->assertSuccessful();
    }
}
