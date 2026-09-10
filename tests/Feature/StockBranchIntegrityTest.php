<?php

namespace Tests\Feature;

use App\Livewire\Stock\StockAdd;
use App\Livewire\Stock\StockDapurCreate;
use App\Models\Branch;
use App\Models\Ingredients;
use App\Models\RiwayatStock;
use App\Models\SatuanBahan;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StockBranchIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;
    protected Branch $branchB;
    protected Branch $inactiveBranch;
    protected User $userBranchA;
    protected User $superadminWithoutBranch;
    protected SatuanBahan $satuanKg;

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
            'nama_cabang' => 'Cabang Tutup',
            'kode_cabang' => 'TTP',
            'is_active' => false,
        ]);

        $this->userBranchA = User::factory()->create([
            'branch_id' => $this->branchA->id,
        ]);
        $this->userBranchA->assignRole('manager');

        $this->superadminWithoutBranch = User::factory()->create([
            'branch_id' => null,
        ]);
        $this->superadminWithoutBranch->assignRole('superadmin');

        $this->satuanKg = SatuanBahan::create(['nama_satuan' => 'Kg']);
    }

    /**
     * INV-P0-03: User Branch A membuat ingredient -> branch_id = Branch A
     * and Initial RiwayatStock has riwayat_stock.branch_id = ingredient.branch_id.
     */
    public function test_user_branch_creates_ingredient_attached_to_their_branch_with_atomic_history(): void
    {
        Livewire::actingAs($this->userBranchA)
            ->test(StockDapurCreate::class)
            ->set('nama_bahan', 'Tepung Terigu')
            ->set('stok', 25)
            ->set('hpp', 12000)
            ->set('satuan_id', $this->satuanKg->id)
            ->call('simpan')
            ->assertDispatched('showToast', type: 'success', message: 'Bahan berhasil disimpan!');

        $ingredient = Ingredients::where('nama_bahan', 'Tepung Terigu')->first();
        $this->assertNotNull($ingredient);
        $this->assertEquals($this->branchA->id, $ingredient->branch_id);

        $history = RiwayatStock::where('ingredient_id', $ingredient->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals($ingredient->branch_id, $history->branch_id);
        $this->assertEquals($this->branchA->id, $history->branch_id);
    }

    /**
     * INV-P0-03: User Branch A mencoba mengirim branch_id = Branch B
     * Backend harus mengabaikan client branch selection dan tetap menggunakan Branch A.
     */
    public function test_user_branch_client_tampering_branch_id_is_ignored_and_remains_user_branch(): void
    {
        Livewire::actingAs($this->userBranchA)
            ->test(StockDapurCreate::class)
            ->set('nama_bahan', 'Kopi Susu Bahan')
            ->set('stok', 15)
            ->set('hpp', 20000)
            ->set('satuan_id', $this->satuanKg->id)
            ->set('branch_id', $this->branchB->id) // Client tamper attempt
            ->call('simpan')
            ->assertDispatched('showToast', type: 'success', message: 'Bahan berhasil disimpan!');

        $ingredient = Ingredients::where('nama_bahan', 'Kopi Susu Bahan')->first();
        $this->assertNotNull($ingredient);
        $this->assertEquals($this->branchA->id, $ingredient->branch_id);
        $this->assertNotEquals($this->branchB->id, $ingredient->branch_id);

        $history = RiwayatStock::where('ingredient_id', $ingredient->id)->first();
        $this->assertEquals($this->branchA->id, $history->branch_id);
    }

    /**
     * INV-P0-03: Superadmin tanpa branch membuka create dan mendapatkan pilihan branch aktif.
     */
    public function test_superadmin_without_branch_sees_branch_selector_on_create_mode(): void
    {
        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class)
            ->assertSeeHtml('wire:model="branch_id"')
            ->assertSee('Cabang *')
            ->assertSee('Cabang Alpha')
            ->assertSee('Cabang Beta')
            ->assertDontSee('Cabang Tutup'); // Inactive branch is not offered
    }

    /**
     * INV-P0-03: Superadmin tanpa branch tidak memilih branch -> validation gagal, tidak ada record.
     */
    public function test_superadmin_without_branch_fails_validation_without_branch_selection(): void
    {
        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class)
            ->set('nama_bahan', 'Bahan Tanpa Cabang')
            ->set('stok', 10)
            ->set('hpp', 5000)
            ->set('satuan_id', $this->satuanKg->id)
            ->set('branch_id', null)
            ->call('simpan')
            ->assertHasErrors(['branch_id' => 'required']);

        $this->assertDatabaseMissing('ingredients', [
            'nama_bahan' => 'Bahan Tanpa Cabang',
        ]);
        $this->assertEquals(0, RiwayatStock::count());
    }

    /**
     * INV-P0-03: Superadmin tanpa branch memilih Branch A -> ingredient & stok awal di Branch A.
     */
    public function test_superadmin_without_branch_can_create_ingredient_with_selected_branch(): void
    {
        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class)
            ->set('nama_bahan', 'Sirup Karamel')
            ->set('stok', 5)
            ->set('hpp', 45000)
            ->set('satuan_id', $this->satuanKg->id)
            ->set('branch_id', $this->branchA->id)
            ->call('simpan')
            ->assertDispatched('showToast', type: 'success', message: 'Bahan berhasil disimpan!');

        $ingredient = Ingredients::where('nama_bahan', 'Sirup Karamel')->first();
        $this->assertNotNull($ingredient);
        $this->assertEquals($this->branchA->id, $ingredient->branch_id);

        $history = RiwayatStock::where('ingredient_id', $ingredient->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->branchA->id, $history->branch_id);
    }

    /**
     * INV-P0-03: Superadmin memilih branch yang tidak aktif -> validation gagal.
     */
    public function test_superadmin_without_branch_selecting_inactive_branch_fails_validation(): void
    {
        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class)
            ->set('nama_bahan', 'Bahan Inactive Branch')
            ->set('stok', 5)
            ->set('hpp', 10000)
            ->set('satuan_id', $this->satuanKg->id)
            ->set('branch_id', $this->inactiveBranch->id)
            ->call('simpan')
            ->assertHasErrors(['branch_id']);

        $this->assertDatabaseMissing('ingredients', [
            'nama_bahan' => 'Bahan Inactive Branch',
        ]);
    }

    /**
     * INV-P0-03: Create ingredient tidak pernah menghasilkan branch_id = NULL.
     */
    public function test_create_ingredient_never_results_in_null_branch_id(): void
    {
        Livewire::actingAs($this->userBranchA)
            ->test(StockDapurCreate::class)
            ->set('nama_bahan', 'Item Branch User')
            ->set('stok', 10)
            ->set('satuan_id', $this->satuanKg->id)
            ->call('simpan');

        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class)
            ->set('nama_bahan', 'Item Superadmin')
            ->set('stok', 10)
            ->set('satuan_id', $this->satuanKg->id)
            ->set('branch_id', $this->branchB->id)
            ->call('simpan');

        $this->assertEquals(0, Ingredients::whereNull('branch_id')->count());
        $this->assertEquals(0, RiwayatStock::whereNull('branch_id')->count());
    }

    /**
     * INV-P0-04: Tambah stok manual pada ingredient Branch A menghasilkan riwayat_stocks.branch_id = Branch A.
     */
    public function test_stock_add_manual_restock_follows_ingredient_branch(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Teh Melati',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 8000,
            'branch_id' => $this->branchA->id,
        ]);

        Livewire::actingAs($this->userBranchA)
            ->test(StockAdd::class)
            ->set('ingredient_id', $ingredient->id)
            ->set('qty', 20)
            ->set('keterangan', 'Restock Mingguan')
            ->call('tambahStok')
            ->assertDispatched('showToast', type: 'success', message: 'Stock berhasil ditambah!');

        $ingredient->refresh();
        $this->assertEquals(30, $ingredient->stok);

        $history = RiwayatStock::where('ingredient_id', $ingredient->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->branchA->id, $history->branch_id);
        $this->assertEquals(10, $history->qty_before);
        $this->assertEquals(30, $history->qty_after);
    }

    /**
     * INV-P0-04: Superadmin tanpa user branch menambah stok Ingredient Branch A:
     * riwayat_stocks.branch_id = Branch A (bukan NULL).
     */
    public function test_stock_add_superadmin_without_branch_restock_follows_ingredient_branch(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Cokelat Bubuk',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 5,
            'hpp' => 25000,
            'branch_id' => $this->branchA->id,
        ]);

        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockAdd::class)
            ->set('ingredient_id', $ingredient->id)
            ->set('qty', 15)
            ->set('keterangan', 'Restock Superadmin')
            ->call('tambahStok')
            ->assertDispatched('showToast', type: 'success', message: 'Stock berhasil ditambah!');

        $ingredient->refresh();
        $this->assertEquals(20, $ingredient->stok);

        $history = RiwayatStock::where('ingredient_id', $ingredient->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->branchA->id, $history->branch_id);
    }

    /**
     * INV-P0-04: Ingredient dengan branch_id = NULL tidak boleh menghasilkan manual stock history baru.
     * Ditolak gagal aman dengan pesan yang jelas dan stok tidak berubah.
     */
    public function test_stock_add_branchless_ingredient_rejected_safely(): void
    {
        // Simulasi data legacy dengan branch_id = NULL
        $branchlessIngredient = Ingredients::create([
            'nama_bahan' => 'Legacy Branchless Ingredient',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 5000,
            'branch_id' => null,
        ]);

        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockAdd::class)
            ->set('ingredient_id', $branchlessIngredient->id)
            ->set('qty', 10)
            ->call('tambahStok')
            ->assertHasErrors(['ingredient_id'])
            ->assertDispatched('showToast', type: 'error', message: 'Bahan baku belum memiliki cabang. Perbaiki data cabang terlebih dahulu sebelum menambah stok.');

        $branchlessIngredient->refresh();
        $this->assertEquals(10, $branchlessIngredient->stok); // Stok tidak bertambah
        $this->assertEquals(0, RiwayatStock::count()); // Tidak ada riwayat tercipta
    }

    /**
     * Edit mode tidak merender selector branch dan mempertahankan branch existing.
     */
    public function test_edit_mode_does_not_show_branch_selector_and_retains_existing_branch(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Bahan Edit Mode',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 20,
            'hpp' => 10000,
            'branch_id' => $this->branchA->id,
        ]);

        Livewire::actingAs($this->superadminWithoutBranch)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode($ingredient->id)])
            ->assertDontSeeHtml('wire:model="branch_id"')
            ->set('nama_bahan', 'Bahan Edit Mode Updated')
            ->call('update')
            ->assertDispatched('showToast', type: 'success', message: 'Bahan berhasil diupdate!');

        $ingredient->refresh();
        $this->assertEquals('Bahan Edit Mode Updated', $ingredient->nama_bahan);
        $this->assertEquals($this->branchA->id, $ingredient->branch_id); // Branch remains unchanged
    }
}
