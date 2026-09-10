<?php

namespace Tests\Feature;

use App\Livewire\Stock\StockDapur;
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
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class IngredientDeleteGuardTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;
    protected Branch $branchB;
    protected User $userA;
    protected User $superadmin;
    protected SatuanBahan $satuan;
    protected PriceTier $priceTier;

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

        $this->superadmin = User::factory()->create(['branch_id' => null]);
        $this->superadmin->assignRole('superadmin');

        $this->satuan = SatuanBahan::create(['nama_satuan' => 'Gram']);
    }

    private function createIngredient(Branch $branch, string $nama = 'Bahan Test', float $stok = 100): Ingredients
    {
        return Ingredients::create([
            'branch_id' => $branch->id,
            'nama_bahan' => $nama,
            'satuan_id' => $this->satuan->id,
            'stok' => $stok,
            'hpp' => 1000,
        ]);
    }

    /** 1. Ingredient tanpa recipe bisa dihapus */
    public function test_ingredient_without_recipe_can_be_deleted(): void
    {
        $ingredient = $this->createIngredient($this->branchA, 'Bahan Bebas');

        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode((string) $ingredient->id))
            ->assertDispatched('showToast', type: 'success', message: 'Bahan berhasil dihapus.');

        $this->assertSoftDeleted('ingredients', ['id' => $ingredient->id]);
    }

    /** 2. Ingredient yang dipakai menu_ingredients tidak bisa dihapus */
    public function test_ingredient_used_in_base_recipe_cannot_be_deleted(): void
    {
        $ingredient = $this->createIngredient($this->branchA, 'Biji Kopi Resep');

        $category = Category::create(['nama' => 'Minuman']);
        $menu = Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Espresso',
            'harga' => 15000,
            'h_pokok' => 5000,
            'is_active' => true,
        ]);

        MenuIngredients::create([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredient->id,
            'qty' => 15,
        ]);

        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode((string) $ingredient->id))
            ->assertDispatched('showToast', type: 'error', message: 'Bahan tidak dapat dihapus karena masih digunakan dalam resep menu.');

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('menu_ingredients', [
            'ingredient_id' => $ingredient->id,
            'menu_id' => $menu->id,
        ]);
    }

    /** 3. Ingredient yang dipakai variant_option_ingredients tidak bisa dihapus */
    public function test_ingredient_used_in_variant_recipe_cannot_be_deleted(): void
    {
        $ingredient = $this->createIngredient($this->branchA, 'Susu Oat');

        $vg = VariantGroup::create([
            'nama_group' => 'Jenis Susu',
            'selection_type' => 'single',
            'is_required' => false,
        ]);

        $variantOption = VariantOption::create([
            'variant_group_id' => $vg->id,
            'nama_opsi' => 'Oatmilk',
            'extra_price' => 7000,
        ]);

        $variantOption->ingredients()->attach($ingredient->id, ['qty' => 100]);

        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode((string) $ingredient->id))
            ->assertDispatched('showToast', type: 'error', message: 'Bahan tidak dapat dihapus karena masih digunakan dalam resep varian.');

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('variant_option_ingredients', [
            'ingredient_id' => $ingredient->id,
            'variant_option_id' => $variantOption->id,
        ]);
    }

    /** 4. Ingredient yang dipakai base + variant tidak bisa dihapus */
    public function test_ingredient_used_in_both_base_and_variant_recipe_cannot_be_deleted(): void
    {
        $ingredient = $this->createIngredient($this->branchA, 'Gula Cair');

        $category = Category::create(['nama' => 'Minuman']);
        $menu = Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Kopi Manis',
            'harga' => 18000,
            'h_pokok' => 6000,
            'is_active' => true,
        ]);

        MenuIngredients::create([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredient->id,
            'qty' => 10,
        ]);

        $vg = VariantGroup::create([
            'nama_group' => 'Extra Manis',
            'selection_type' => 'single',
            'is_required' => false,
        ]);

        $variantOption = VariantOption::create([
            'variant_group_id' => $vg->id,
            'nama_opsi' => 'Double Sugar',
            'extra_price' => 2000,
        ]);

        $variantOption->ingredients()->attach($ingredient->id, ['qty' => 10]);

        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode((string) $ingredient->id))
            ->assertDispatched('showToast', type: 'error', message: 'Bahan tidak dapat dihapus karena masih digunakan dalam resep menu atau varian.');

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'deleted_at' => null,
        ]);
    }

    /** 5. Saat delete diblokir, ingredients.deleted_at tetap NULL */
    public function test_blocked_delete_ensures_deleted_at_remains_null(): void
    {
        $ingredient = $this->createIngredient($this->branchA, 'Sirup Hazelnut');

        $category = Category::create(['nama' => 'Minuman']);
        $menu = Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Hazelnut Latte',
            'harga' => 25000,
            'h_pokok' => 10000,
            'is_active' => true,
        ]);

        MenuIngredients::create([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredient->id,
            'qty' => 20,
        ]);

        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode((string) $ingredient->id));

        $this->assertNull($ingredient->fresh()->deleted_at);
    }

    /** 6. Saat delete diblokir, row menu_ingredients tetap ada */
    public function test_blocked_delete_leaves_menu_ingredients_rows_intact(): void
    {
        $ingredient = $this->createIngredient($this->branchA, 'Matcha Powder');

        $category = Category::create(['nama' => 'Minuman']);
        $menu = Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Matcha Latte',
            'harga' => 22000,
            'h_pokok' => 8000,
            'is_active' => true,
        ]);

        $pivot = MenuIngredients::create([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredient->id,
            'qty' => 15,
        ]);

        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode((string) $ingredient->id));

        $this->assertDatabaseHas('menu_ingredients', [
            'id' => $pivot->id,
            'ingredient_id' => $ingredient->id,
            'menu_id' => $menu->id,
        ]);
    }

    /** 7. Saat delete diblokir, row variant_option_ingredients tetap ada */
    public function test_blocked_delete_leaves_variant_option_ingredients_rows_intact(): void
    {
        $ingredient = $this->createIngredient($this->branchA, 'Coklat Bubuk');

        $vg = VariantGroup::create([
            'nama_group' => 'Taburan',
            'selection_type' => 'single',
            'is_required' => false,
        ]);

        $variantOption = VariantOption::create([
            'variant_group_id' => $vg->id,
            'nama_opsi' => 'Choco Powder',
            'extra_price' => 3000,
        ]);

        $variantOption->ingredients()->attach($ingredient->id, ['qty' => 5]);

        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode((string) $ingredient->id));

        $this->assertDatabaseHas('variant_option_ingredients', [
            'variant_option_id' => $variantOption->id,
            'ingredient_id' => $ingredient->id,
        ]);
    }

    /** 8. Toast/error yang jelas dikirim saat delete diblokir */
    public function test_clear_error_toast_dispatched_on_blocked_delete(): void
    {
        $ingredient = $this->createIngredient($this->branchA, 'Caramel Sauce');

        $category = Category::create(['nama' => 'Minuman']);
        $menu = Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Caramel Macchiato',
            'harga' => 28000,
            'h_pokok' => 12000,
            'is_active' => true,
        ]);

        MenuIngredients::create([
            'menu_id' => $menu->id,
            'ingredient_id' => $ingredient->id,
            'qty' => 20,
        ]);

        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode((string) $ingredient->id))
            ->assertDispatched('showToast', function ($event, $params) {
                return ($params['type'] ?? '') === 'error'
                    && str_contains($params['message'] ?? '', 'Bahan tidak dapat dihapus');
            });
    }

    /** 9. Ingredient branch lain tidak dapat dihapus oleh normal user branch A */
    public function test_user_branch_a_cannot_delete_ingredient_of_branch_b(): void
    {
        $ingredientB = $this->createIngredient($this->branchB, 'Bahan Cabang B Bebas');

        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode((string) $ingredientB->id))
            ->assertDispatched('showToast', type: 'error', message: 'Bahan tidak ditemukan.');

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredientB->id,
            'deleted_at' => null,
        ]);
    }

    /** 10. Invalid encoded ID gagal aman */
    public function test_invalid_encoded_id_fails_safely(): void
    {
        $ingredient = $this->createIngredient($this->branchA, 'Bahan Aman');

        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', 'not_valid_base64!@#$')
            ->assertDispatched('showToast', type: 'error', message: 'Bahan tidak ditemukan.');

        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode('abc'))
            ->assertDispatched('showToast', type: 'error', message: 'Bahan tidak ditemukan.');

        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode('-10'))
            ->assertDispatched('showToast', type: 'error', message: 'Bahan tidak ditemukan.');

        $this->assertDatabaseHas('ingredients', [
            'id' => $ingredient->id,
            'deleted_at' => null,
        ]);
    }

    /** 11. Nonexistent valid encoded ID gagal aman */
    public function test_nonexistent_valid_encoded_id_fails_safely(): void
    {
        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode('99999999'))
            ->assertDispatched('showToast', type: 'error', message: 'Bahan tidak ditemukan.');
    }

    /** 12. Ingredient yang tidak direferensikan tetap soft-delete, bukan force-delete */
    public function test_unreferenced_ingredient_is_soft_deleted_not_force_deleted(): void
    {
        $ingredient = $this->createIngredient($this->branchA, 'Bahan Soft Delete Saja');

        Livewire::actingAs($this->userA)
            ->test(StockDapur::class)
            ->call('deleteIngredient', base64_encode((string) $ingredient->id))
            ->assertDispatched('showToast', type: 'success', message: 'Bahan berhasil dihapus.');

        // Record still exists in database with deleted_at set
        $trashed = Ingredients::withTrashed()->find($ingredient->id);
        $this->assertNotNull($trashed);
        $this->assertTrue($trashed->trashed());
        $this->assertNotNull($trashed->deleted_at);

        // Does not exist in active query
        $this->assertNull(Ingredients::find($ingredient->id));
    }
}
