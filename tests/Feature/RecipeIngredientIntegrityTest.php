<?php

namespace Tests\Feature;

use App\Livewire\Menu\MenuIngredient;
use App\Livewire\Stock\StockDapur;
use App\Livewire\Variant\ManageVariantIngredient;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Ingredients;
use App\Models\Menu;
use App\Models\MenuIngredients;
use App\Models\PriceTier;
use App\Models\SatuanBahan;
use App\Models\User;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecipeIngredientIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;
    protected Branch $branchB;
    protected User $userA;
    protected User $userB;
    protected User $superadmin;
    protected SatuanBahan $satuan;
    protected PriceTier $priceTier;
    protected Ingredients $ingredientA;
    protected Ingredients $ingredientB;
    protected Menu $menuA;
    protected VariantOption $variantOption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->priceTier = PriceTier::first() ?? PriceTier::create(['nama_tier' => 'Regular', 'is_active' => true]);

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
            'nama_menu' => 'Kopi A',
            'harga' => 20000,
            'h_pokok' => 10000,
            'is_active' => true,
        ]);

        $vg = VariantGroup::create([
            'nama_group' => 'Ukuran',
            'selection_type' => 'single',
            'is_required' => false,
        ]);
        $this->menuA->variantGroups()->attach($vg->id);

        $this->variantOption = VariantOption::create([
            'variant_group_id' => $vg->id,
            'nama_opsi' => 'Large',
            'extra_price' => 5000,
        ]);
    }

    /** 1. Base recipe dapat attach ingredient branch user */
    public function test_base_recipe_can_attach_ingredient_of_user_branch(): void
    {
        Livewire::actingAs($this->userA)
            ->test(MenuIngredient::class)
            ->set('menu_id', $this->menuA->id)
            ->set('ingredient_id', $this->ingredientA->id)
            ->set('qty', 15)
            ->call('addIngredient')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('menu_ingredients', [
            'menu_id' => $this->menuA->id,
            'ingredient_id' => $this->ingredientA->id,
            'qty' => 15,
        ]);
    }

    /** 2. Base recipe menolak ingredient branch lain melalui Livewire tampering */
    public function test_base_recipe_rejects_other_branch_ingredient_via_tampering(): void
    {
        Livewire::actingAs($this->userA)
            ->test(MenuIngredient::class)
            ->set('menu_id', $this->menuA->id)
            ->set('ingredient_id', $this->ingredientB->id) // Branch B ingredient
            ->set('qty', 10)
            ->call('addIngredient')
            ->assertHasErrors(['ingredient_id']);

        $this->assertDatabaseMissing('menu_ingredients', [
            'menu_id' => $this->menuA->id,
            'ingredient_id' => $this->ingredientB->id,
        ]);
    }

    /** 3. Base recipe menolak soft-deleted ingredient */
    public function test_base_recipe_rejects_soft_deleted_ingredient(): void
    {
        $this->ingredientA->delete();
        $this->assertSoftDeleted('ingredients', ['id' => $this->ingredientA->id]);

        Livewire::actingAs($this->userA)
            ->test(MenuIngredient::class)
            ->set('menu_id', $this->menuA->id)
            ->set('ingredient_id', $this->ingredientA->id)
            ->set('qty', 10)
            ->call('addIngredient')
            ->assertHasErrors(['ingredient_id']);

        $this->assertDatabaseMissing('menu_ingredients', [
            'menu_id' => $this->menuA->id,
            'ingredient_id' => $this->ingredientA->id,
        ]);
    }

    /** 4. Base recipe menolak nonexistent ingredient */
    public function test_base_recipe_rejects_nonexistent_ingredient(): void
    {
        Livewire::actingAs($this->userA)
            ->test(MenuIngredient::class)
            ->set('menu_id', $this->menuA->id)
            ->set('ingredient_id', 999999)
            ->set('qty', 10)
            ->call('addIngredient')
            ->assertHasErrors(['ingredient_id']);

        $this->assertEquals(0, MenuIngredients::count());
    }

    /** 5. Base recipe tidak membuat pivot jika validation gagal */
    public function test_base_recipe_does_not_create_pivot_on_validation_failure(): void
    {
        Livewire::actingAs($this->userA)
            ->test(MenuIngredient::class)
            ->set('menu_id', $this->menuA->id)
            ->set('ingredient_id', $this->ingredientA->id)
            ->set('qty', 0) // Invalid qty
            ->call('addIngredient')
            ->assertHasErrors(['qty']);

        $this->assertEquals(0, MenuIngredients::count());
    }

    /** 6. Variant recipe dapat attach ingredient branch user */
    public function test_variant_recipe_can_attach_ingredient_of_user_branch(): void
    {
        Livewire::actingAs($this->userA)
            ->test(ManageVariantIngredient::class, ['id' => base64_encode((string) $this->variantOption->id)])
            ->set('newIngredientId', $this->ingredientA->id)
            ->set('newQty', 5)
            ->call('addIngredient')
            ->assertHasNoErrors()
            ->assertDispatched('showToast', type: 'success');

        $this->assertDatabaseHas('variant_option_ingredients', [
            'variant_option_id' => $this->variantOption->id,
            'ingredient_id' => $this->ingredientA->id,
            'qty' => 5,
        ]);
    }

    /** 7. Variant recipe menolak ingredient branch lain melalui Livewire tampering */
    public function test_variant_recipe_rejects_other_branch_ingredient_via_tampering(): void
    {
        Livewire::actingAs($this->userA)
            ->test(ManageVariantIngredient::class, ['id' => base64_encode((string) $this->variantOption->id)])
            ->set('newIngredientId', $this->ingredientB->id) // Branch B ingredient
            ->set('newQty', 5)
            ->call('addIngredient')
            ->assertHasErrors(['newIngredientId']);

        $this->assertDatabaseMissing('variant_option_ingredients', [
            'variant_option_id' => $this->variantOption->id,
            'ingredient_id' => $this->ingredientB->id,
        ]);
    }

    /** 8. Variant recipe menolak soft-deleted ingredient */
    public function test_variant_recipe_rejects_soft_deleted_ingredient(): void
    {
        $this->ingredientA->delete();
        $this->assertSoftDeleted('ingredients', ['id' => $this->ingredientA->id]);

        Livewire::actingAs($this->userA)
            ->test(ManageVariantIngredient::class, ['id' => base64_encode((string) $this->variantOption->id)])
            ->set('newIngredientId', $this->ingredientA->id)
            ->set('newQty', 5)
            ->call('addIngredient')
            ->assertHasErrors(['newIngredientId']);

        $this->assertEquals(0, $this->variantOption->ingredients()->count());
    }

    /** 9. Variant recipe menolak nonexistent ingredient */
    public function test_variant_recipe_rejects_nonexistent_ingredient(): void
    {
        Livewire::actingAs($this->userA)
            ->test(ManageVariantIngredient::class, ['id' => base64_encode((string) $this->variantOption->id)])
            ->set('newIngredientId', 999999)
            ->set('newQty', 5)
            ->call('addIngredient')
            ->assertHasErrors(['newIngredientId']);

        $this->assertEquals(0, $this->variantOption->ingredients()->count());
    }

    /** 10. Variant ingredient dropdown normal user tidak memuat ingredient branch lain */
    public function test_variant_ingredient_dropdown_does_not_contain_other_branch_ingredients(): void
    {
        Livewire::actingAs($this->userA)
            ->test(ManageVariantIngredient::class, ['id' => base64_encode((string) $this->variantOption->id)])
            ->assertViewHas('allIngredients', function ($ingredients) {
                return $ingredients->contains('id', $this->ingredientA->id)
                    && ! $ingredients->contains('id', $this->ingredientB->id);
            });
    }

    /** 11. Existing duplicate protection tetap bekerja */
    public function test_variant_recipe_duplicate_protection_works(): void
    {
        $component = Livewire::actingAs($this->userA)
            ->test(ManageVariantIngredient::class, ['id' => base64_encode((string) $this->variantOption->id)])
            ->set('newIngredientId', $this->ingredientA->id)
            ->set('newQty', 5)
            ->call('addIngredient')
            ->assertHasNoErrors();

        // Attempt adding the same ingredient again
        $component->set('newIngredientId', $this->ingredientA->id)
            ->set('newQty', 3)
            ->call('addIngredient')
            ->assertHasErrors(['newIngredientId']);

        $this->assertEquals(1, $this->variantOption->ingredients()->count());
    }

    /** 12. Qty validation tetap bekerja */
    public function test_recipe_qty_validation_works(): void
    {
        // Base recipe qty <= 0
        Livewire::actingAs($this->userA)
            ->test(MenuIngredient::class)
            ->set('menu_id', $this->menuA->id)
            ->set('ingredient_id', $this->ingredientA->id)
            ->set('qty', 0)
            ->call('addIngredient')
            ->assertHasErrors(['qty']);

        // Variant recipe newQty <= 0
        Livewire::actingAs($this->userA)
            ->test(ManageVariantIngredient::class, ['id' => base64_encode((string) $this->variantOption->id)])
            ->set('newIngredientId', $this->ingredientA->id)
            ->set('newQty', 0)
            ->call('addIngredient')
            ->assertHasErrors(['newQty']);
    }

    /** 13. Delete guard tetap menolak ingredient setelah recipe berhasil dibuat */
    public function test_delete_guard_blocks_ingredient_after_recipe_is_created(): void
    {
        // Add to base recipe
        Livewire::actingAs($this->userA)
            ->test(MenuIngredient::class)
            ->set('menu_id', $this->menuA->id)
            ->set('ingredient_id', $this->ingredientA->id)
            ->set('qty', 10)
            ->call('addIngredient')
            ->assertHasNoErrors();

        // Now attempt deleting the ingredient in StockDapur
        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode((string) $this->ingredientA->id))
            ->assertDispatched('showToast', type: 'error', message: 'Bahan tidak dapat dihapus karena masih digunakan dalam resep menu.');

        $this->assertDatabaseHas('ingredients', [
            'id' => $this->ingredientA->id,
            'deleted_at' => null,
        ]);
    }

    /** 14. Superadmin respects soft-delete and nonexistent guards */
    public function test_superadmin_respects_soft_delete_and_nonexistent_guards(): void
    {
        $this->ingredientA->delete();

        // Base recipe
        Livewire::actingAs($this->superadmin)
            ->test(MenuIngredient::class)
            ->set('menu_id', $this->menuA->id)
            ->set('ingredient_id', $this->ingredientA->id)
            ->set('qty', 10)
            ->call('addIngredient')
            ->assertHasErrors(['ingredient_id']);

        // Variant recipe
        Livewire::actingAs($this->superadmin)
            ->test(ManageVariantIngredient::class, ['id' => base64_encode((string) $this->variantOption->id)])
            ->set('newIngredientId', $this->ingredientA->id)
            ->set('newQty', 5)
            ->call('addIngredient')
            ->assertHasErrors(['newIngredientId']);
    }
}
