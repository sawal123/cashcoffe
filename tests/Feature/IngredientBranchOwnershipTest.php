<?php

namespace Tests\Feature;

use App\Livewire\Stock\StockDapurCreate;
use App\Models\Branch;
use App\Models\Ingredients;
use App\Models\SatuanBahan;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class IngredientBranchOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;
    protected Branch $branchB;
    protected Branch $inactiveBranch;
    protected User $userBranchA;
    protected User $userBranchB;
    protected User $superadminWithoutBranch;
    protected SatuanBahan $satuanKg;
    protected SatuanBahan $satuanGram;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->branchA = Branch::create([
            'nama_cabang' => 'Cabang Alpha',
            'kode_cabang' => 'ALP',
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'nama_cabang' => 'Cabang Beta',
            'kode_cabang' => 'BET',
            'is_active' => true,
        ]);

        $this->inactiveBranch = Branch::create([
            'nama_cabang' => 'Cabang Nonaktif',
            'kode_cabang' => 'NOA',
            'is_active' => false,
        ]);

        $this->userBranchA = User::factory()->create([
            'branch_id' => $this->branchA->id,
        ]);
        $this->userBranchA->assignRole('manager');

        $this->userBranchB = User::factory()->create([
            'branch_id' => $this->branchB->id,
        ]);
        $this->userBranchB->assignRole('manager');

        $this->superadminWithoutBranch = User::factory()->create([
            'branch_id' => null,
        ]);
        $this->superadminWithoutBranch->assignRole('superadmin');

        $this->satuanKg = SatuanBahan::create(['nama_satuan' => 'Kg']);
        $this->satuanGram = SatuanBahan::create(['nama_satuan' => 'Gram']);
    }

    /**
     * 1. Normal Branch A create menghasilkan ingredient Branch A.
     */
    public function test_normal_user_create_attaches_ingredient_to_user_branch(): void
    {
        Livewire::actingAs($this->userBranchA)
            ->test(StockDapurCreate::class)
            ->set('nama_bahan', 'Tepung Terigu Alpha')
            ->set('stok', 25)
            ->set('hpp', 12000)
            ->set('satuan_id', $this->satuanKg->id)
            ->call('simpan')
            ->assertHasNoErrors();

        $ingredient = Ingredients::withoutGlobalScopes()
            ->where('nama_bahan', 'Tepung Terigu Alpha')
            ->first();

        $this->assertNotNull($ingredient);
        $this->assertSame($this->branchA->id, (int) $ingredient->branch_id);
    }

    /**
     * 2. Client normal user tidak dapat memaksa branch B saat create via Livewire tampering.
     */
    public function test_normal_user_cannot_force_branch_b_on_create_via_tampering(): void
    {
        Livewire::actingAs($this->userBranchA)
            ->test(StockDapurCreate::class)
            ->set('nama_bahan', 'Kopi Alpha Proteksi')
            ->set('stok', 10)
            ->set('hpp', 50000)
            ->set('satuan_id', $this->satuanKg->id)
            ->set('branch_id', $this->branchB->id)
            ->call('simpan')
            ->assertHasNoErrors();

        $ingredient = Ingredients::withoutGlobalScopes()
            ->where('nama_bahan', 'Kopi Alpha Proteksi')
            ->first();

        $this->assertNotNull($ingredient);
        $this->assertSame($this->branchA->id, (int) $ingredient->branch_id);
        $this->assertNotSame($this->branchB->id, (int) $ingredient->branch_id);
    }

    /**
     * 3. Superadmin tanpa branch create tetap wajib memilih branch.
     */
    public function test_superadmin_without_branch_must_select_branch_on_create(): void
    {
        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class)
            ->set('nama_bahan', 'Gula Superadmin')
            ->set('stok', 15)
            ->set('hpp', 14000)
            ->set('satuan_id', $this->satuanKg->id)
            ->set('branch_id', null)
            ->call('simpan')
            ->assertHasErrors(['branch_id' => 'required']);

        $this->assertDatabaseMissing('ingredients', [
            'nama_bahan' => 'Gula Superadmin',
        ]);
    }

    /**
     * 4. Superadmin create hanya menerima active branch.
     */
    public function test_superadmin_create_only_accepts_active_branch(): void
    {
        // Reject inactive branch
        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class)
            ->set('nama_bahan', 'Garam Inactive Test')
            ->set('stok', 5)
            ->set('hpp', 8000)
            ->set('satuan_id', $this->satuanKg->id)
            ->set('branch_id', $this->inactiveBranch->id)
            ->call('simpan')
            ->assertHasErrors(['branch_id']);

        $this->assertDatabaseMissing('ingredients', [
            'nama_bahan' => 'Garam Inactive Test',
        ]);

        // Accept active branch
        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class)
            ->set('nama_bahan', 'Garam Active Test')
            ->set('stok', 5)
            ->set('hpp', 8000)
            ->set('satuan_id', $this->satuanKg->id)
            ->set('branch_id', $this->branchA->id)
            ->call('simpan')
            ->assertHasNoErrors();

        $ingredient = Ingredients::withoutGlobalScopes()
            ->where('nama_bahan', 'Garam Active Test')
            ->first();

        $this->assertNotNull($ingredient);
        $this->assertSame($this->branchA->id, (int) $ingredient->branch_id);
    }

    /**
     * 5. Edit normal user menampilkan current branch ownership.
     */
    public function test_edit_normal_user_displays_current_branch_ownership(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Bahan Alpha 1',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 20,
            'hpp' => 15000,
            'branch_id' => $this->branchA->id,
        ]);

        Livewire::actingAs($this->userBranchA)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode($ingredient->id)])
            ->assertSet('ingredient_id', $ingredient->id)
            ->assertSet('ingredient_branch_id', $this->branchA->id)
            ->assertSet('ingredient_branch_name', $this->branchA->nama_cabang)
            ->assertSee($this->branchA->nama_cabang);
    }

    /**
     * 6. Edit superadmin menampilkan current branch ownership.
     */
    public function test_edit_superadmin_displays_current_branch_ownership(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Bahan Beta 1',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 30,
            'hpp' => 25000,
            'branch_id' => $this->branchB->id,
        ]);

        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode($ingredient->id)])
            ->assertSet('ingredient_id', $ingredient->id)
            ->assertSet('ingredient_branch_id', $this->branchB->id)
            ->assertSet('ingredient_branch_name', $this->branchB->nama_cabang)
            ->assertSee($this->branchB->nama_cabang);
    }

    /**
     * 7. Edit tidak menampilkan selector reassignment.
     */
    public function test_edit_does_not_render_reassignment_selector(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Bahan Beta Select Test',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 20000,
            'branch_id' => $this->branchB->id,
        ]);

        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode($ingredient->id)])
            ->assertSet('isEdit', true)
            ->assertDontSeeHtml('wire:model="branch_id"')
            ->assertDontSee('Pilih Cabang')
            ->assertSee('Cabang tidak dapat dipindahkan melalui halaman edit bahan.');
    }

    /**
     * 8. Superadmin memanipulasi $branch_id saat edit -> ownership tidak berubah.
     */
    public function test_superadmin_tampering_branch_id_on_edit_does_not_change_ownership(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Bahan Alpha Tamper Test',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 15,
            'hpp' => 10000,
            'branch_id' => $this->branchA->id,
        ]);

        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode($ingredient->id)])
            ->set('branch_id', $this->branchB->id)
            ->set('nama_bahan', 'Bahan Alpha Tamper Renamed')
            ->call('update')
            ->assertHasNoErrors();

        $fresh = $ingredient->fresh();
        $this->assertSame('Bahan Alpha Tamper Renamed', $fresh->nama_bahan);
        $this->assertSame($this->branchA->id, (int) $fresh->branch_id);
    }

    /**
     * 9. Normal user memanipulasi $branch_id saat edit -> ownership tidak berubah.
     */
    public function test_normal_user_tampering_branch_id_on_edit_does_not_change_ownership(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Bahan Alpha User Tamper',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 12,
            'hpp' => 9000,
            'branch_id' => $this->branchA->id,
        ]);

        Livewire::actingAs($this->userBranchA)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode($ingredient->id)])
            ->set('branch_id', $this->branchB->id)
            ->call('update')
            ->assertHasNoErrors();

        $fresh = $ingredient->fresh();
        $this->assertSame($this->branchA->id, (int) $fresh->branch_id);
    }

    /**
     * 10. Normal user Branch A tidak dapat membuka ingredient Branch B.
     */
    public function test_normal_user_branch_a_cannot_open_ingredient_branch_b(): void
    {
        $ingredientB = Ingredients::create([
            'nama_bahan' => 'Bahan Rahasia Branch B',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 40,
            'hpp' => 30000,
            'branch_id' => $this->branchB->id,
        ]);

        Livewire::actingAs($this->userBranchA)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode($ingredientB->id)])
            ->assertStatus(404);
    }

    /**
     * 11. Superadmin dapat mengedit metadata ingredient Branch B tetapi ownership tetap B.
     */
    public function test_superadmin_can_edit_metadata_of_ingredient_branch_b_but_ownership_remains_b(): void
    {
        $ingredientB = Ingredients::create([
            'nama_bahan' => 'Original B Metadata',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 20,
            'hpp' => 15000,
            'branch_id' => $this->branchB->id,
        ]);

        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode($ingredientB->id)])
            ->set('nama_bahan', 'Updated B Metadata')
            ->set('stok', 35)
            ->set('hpp', 18000)
            ->set('satuan_id', $this->satuanGram->id)
            ->call('update')
            ->assertHasNoErrors();

        $fresh = $ingredientB->fresh();
        $this->assertSame('Updated B Metadata', $fresh->nama_bahan);
        $this->assertEquals(35, $fresh->stok);
        $this->assertEquals(18000, $fresh->hpp);
        $this->assertSame($this->satuanGram->id, (int) $fresh->satuan_id);
        $this->assertSame($this->branchB->id, (int) $fresh->branch_id);
    }

    /**
     * 12. Ingredient branch inactive tetap dapat diedit tanpa reassignment.
     */
    public function test_ingredient_belonging_to_inactive_branch_can_be_edited_without_reassignment(): void
    {
        $ingredientInactive = Ingredients::create([
            'nama_bahan' => 'Bahan Cabang Tutup',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 50,
            'hpp' => 10000,
            'branch_id' => $this->inactiveBranch->id,
        ]);

        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode($ingredientInactive->id)])
            ->assertSet('ingredient_branch_id', $this->inactiveBranch->id)
            ->assertSee('Tidak Aktif')
            ->set('stok', 60)
            ->call('update')
            ->assertHasNoErrors();

        $fresh = $ingredientInactive->fresh();
        $this->assertEquals(60, $fresh->stok);
        $this->assertSame($this->inactiveBranch->id, (int) $fresh->branch_id);
    }

    /**
     * 13. Ingredient active dengan branch_id = NULL gagal aman saat edit (422).
     */
    public function test_ingredient_active_with_null_branch_fails_safely_on_edit(): void
    {
        // Create an ingredient with branch_id = NULL via DB directly to simulate legacy branchless data
        $ingredientId = \Illuminate\Support\Facades\DB::table('ingredients')->insertGetId([
            'nama_bahan' => 'Bahan Branchless Legacy',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 5000,
            'branch_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode($ingredientId)])
            ->assertStatus(422);
    }

    /**
     * 14. Existing canonical ingredient_id #[Locked] protection tetap bekerja.
     */
    public function test_canonical_ingredient_id_locked_protection_remains_functional(): void
    {
        $ingredientA = Ingredients::create([
            'nama_bahan' => 'Bahan Target A',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 5000,
            'branch_id' => $this->branchA->id,
        ]);

        $ingredientB = Ingredients::create([
            'nama_bahan' => 'Bahan Target B',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 20,
            'hpp' => 8000,
            'branch_id' => $this->branchA->id,
        ]);

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode($ingredientA->id)])
            ->set('ingredient_id', $ingredientB->id);
    }

    /**
     * 15. Update nama/stok/HPP/satuan tetap berjalan normal.
     */
    public function test_normal_update_of_fields_succeeds(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Bahan Normal Alpha',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 5000,
            'branch_id' => $this->branchA->id,
        ]);

        Livewire::actingAs($this->userBranchA)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode($ingredient->id)])
            ->set('nama_bahan', 'Bahan Normal Alpha Updated')
            ->set('stok', 22)
            ->set('hpp', 7500)
            ->set('satuan_id', $this->satuanGram->id)
            ->call('update')
            ->assertHasNoErrors();

        $fresh = $ingredient->fresh();
        $this->assertSame('Bahan Normal Alpha Updated', $fresh->nama_bahan);
        $this->assertEquals(22, $fresh->stok);
        $this->assertEquals(7500, $fresh->hpp);
        $this->assertSame($this->satuanGram->id, (int) $fresh->satuan_id);
        $this->assertSame($this->branchA->id, (int) $fresh->branch_id);
    }
}
