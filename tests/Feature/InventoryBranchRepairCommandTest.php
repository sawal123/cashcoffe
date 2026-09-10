<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Ingredients;
use App\Models\RiwayatStock;
use App\Models\SatuanBahan;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryBranchRepairCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;
    protected Branch $branchB;
    protected SatuanBahan $satuanKg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->branchA = Branch::create([
            'nama_cabang' => 'Cabang Medan',
            'kode_cabang' => 'MDN',
            'is_active' => true,
        ]);

        $this->branchB = Branch::create([
            'nama_cabang' => 'Cabang Binjai',
            'kode_cabang' => 'BNJ',
            'is_active' => true,
        ]);

        $this->satuanKg = SatuanBahan::create(['nama_satuan' => 'Kg']);
    }

    /**
     * 1. Command tanpa --apply (dry-run) tidak mengubah database apapun.
     */
    public function test_command_dry_run_does_not_modify_database(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Biji Kopi Gayo',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 50,
            'hpp' => 80000,
            'branch_id' => null,
        ]);

        RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->branchA->id,
            'kode' => 'IN-001',
            'qty' => 50,
            'tipe' => 'in',
            'keterangan' => 'Stok Awal',
        ]);

        $historyNull = RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => null,
            'kode' => 'IN-002',
            'qty' => 10,
            'tipe' => 'in',
            'keterangan' => 'Restock',
        ]);

        $this->artisan('inventory:audit-branch-integrity')
            ->expectsOutputToContain('Mode              : DRY RUN')
            ->expectsOutputToContain('Database changes  : 0')
            ->assertSuccessful();

        $ingredient->refresh();
        $historyNull->refresh();

        $this->assertNull($ingredient->branch_id);
        $this->assertNull($historyNull->branch_id);
    }

    /**
     * 2. Ingredient NULL + histories hanya Branch A -> SAFE.
     * 3. --apply mengubah ingredient menjadi Branch A.
     */
    public function test_ingredient_null_with_single_branch_history_is_safe_and_repaired(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Gula Pasir',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 100,
            'hpp' => 15000,
            'branch_id' => null,
        ]);

        RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->branchA->id,
            'kode' => 'IN-101',
            'qty' => 50,
            'tipe' => 'in',
            'keterangan' => 'Batch 1',
        ]);

        RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->branchA->id,
            'kode' => 'IN-102',
            'qty' => 50,
            'tipe' => 'in',
            'keterangan' => 'Batch 2',
        ]);

        // Dry-run checks safe to repair
        $this->artisan('inventory:audit-branch-integrity')
            ->expectsOutputToContain('Safe to repair  : 1')
            ->expectsOutputToContain('Database changes  : 0')
            ->assertSuccessful();

        // Apply mode repairs
        $this->artisan('inventory:audit-branch-integrity --apply')
            ->expectsOutputToContain('Mode              : APPLY')
            ->expectsOutputToContain('Database changes  : 1')
            ->assertSuccessful();

        $ingredient->refresh();
        $this->assertEquals($this->branchA->id, $ingredient->branch_id);
    }

    /**
     * 4. Ingredient NULL + histories Branch A dan B -> CONFLICT dan tidak berubah.
     */
    public function test_ingredient_null_with_multiple_branches_is_conflict_and_untouched(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Susu Kental Manis',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 20,
            'hpp' => 12000,
            'branch_id' => null,
        ]);

        RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->branchA->id,
            'kode' => 'IN-201',
            'qty' => 10,
            'tipe' => 'in',
            'keterangan' => 'Alpha Batch',
        ]);

        RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->branchB->id,
            'kode' => 'IN-202',
            'qty' => 10,
            'tipe' => 'in',
            'keterangan' => 'Beta Batch',
        ]);

        $this->artisan('inventory:audit-branch-integrity --apply')
            ->expectsOutputToContain('Conflicting     : 1')
            ->expectsOutputToContain('Database changes  : 0')
            ->assertSuccessful();

        $ingredient->refresh();
        $this->assertNull($ingredient->branch_id);
    }

    /**
     * 5. Ingredient NULL tanpa evidence -> UNRESOLVED dan tidak pernah default ke Branch 1.
     */
    public function test_ingredient_null_without_evidence_is_unresolved_and_never_defaults_to_branch_one(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Perisa Vanilla',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 5,
            'hpp' => 30000,
            'branch_id' => null,
        ]);

        $this->artisan('inventory:audit-branch-integrity --apply')
            ->expectsOutputToContain('Unresolved      : 1')
            ->expectsOutputToContain('Database changes  : 0')
            ->assertSuccessful();

        $ingredient->refresh();
        $this->assertNull($ingredient->branch_id);
        $this->assertNotEquals($this->branchA->id, $ingredient->branch_id);
    }

    /**
     * 6. History NULL + ingredient Branch A -> diperbaiki menjadi Branch A.
     */
    public function test_history_null_with_ingredient_branch_a_is_repaired_to_branch_a(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Biji Kopi Robusta',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 40,
            'hpp' => 60000,
            'branch_id' => $this->branchA->id,
        ]);

        $historyNull = RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => null,
            'kode' => 'IN-301',
            'qty' => 40,
            'tipe' => 'in',
            'keterangan' => 'Stok Awal',
        ]);

        $this->artisan('inventory:audit-branch-integrity --apply')
            ->expectsOutputToContain('RiwayatStock NULL : 1')
            ->expectsOutputToContain('Safe to repair  : 1')
            ->expectsOutputToContain('Database changes  : 1')
            ->assertSuccessful();

        $historyNull->refresh();
        $this->assertEquals($this->branchA->id, $historyNull->branch_id);
    }

    /**
     * 7. History NULL + unresolved ingredient -> tetap NULL.
     */
    public function test_history_null_with_unresolved_ingredient_remains_null(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Bahan Misterius',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 5000,
            'branch_id' => null,
        ]);

        $historyNull = RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => null,
            'kode' => 'IN-401',
            'qty' => 10,
            'tipe' => 'in',
            'keterangan' => 'Mystery Batch',
        ]);

        $this->artisan('inventory:audit-branch-integrity --apply')
            ->expectsOutputToContain('RiwayatStock NULL : 1')
            ->expectsOutputToContain('Unresolved      : 1')
            ->expectsOutputToContain('Database changes  : 0')
            ->assertSuccessful();

        $historyNull->refresh();
        $this->assertNull($historyNull->branch_id);
    }

    /**
     * 8. Soft-deleted ingredient tetap diaudit.
     */
    public function test_soft_deleted_ingredient_is_audited_and_repaired(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Bahan Dihapus Tapi Punya History',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 5000,
            'branch_id' => null,
        ]);

        RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->branchB->id,
            'kode' => 'IN-501',
            'qty' => 10,
            'tipe' => 'in',
            'keterangan' => 'Batch Lama',
        ]);

        $ingredient->delete(); // Soft delete
        $this->assertNotNull($ingredient->fresh()->deleted_at);

        $this->artisan('inventory:audit-branch-integrity')
            ->expectsOutputToContain('TRASHED')
            ->expectsOutputToContain('Safe to repair  : 1')
            ->assertSuccessful();

        $this->artisan('inventory:audit-branch-integrity --apply')
            ->assertSuccessful();

        $ingredientWithTrashed = Ingredients::withTrashed()->find($ingredient->id);
        $this->assertEquals($this->branchB->id, $ingredientWithTrashed->branch_id);
    }

    /**
     * 9. Soft-deleted history tetap diaudit dan di-repair jika deterministic.
     */
    public function test_soft_deleted_history_is_audited_and_repaired(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Matcha Powder',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 15,
            'hpp' => 50000,
            'branch_id' => $this->branchA->id,
        ]);

        $historyNull = RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => null,
            'kode' => 'IN-601',
            'qty' => 15,
            'tipe' => 'in',
            'keterangan' => 'Soft-deleted history',
        ]);

        $historyNull->delete(); // Soft delete
        $this->assertNotNull($historyNull->fresh()->deleted_at);

        $this->artisan('inventory:audit-branch-integrity --apply')
            ->assertSuccessful();

        $repairedHistory = RiwayatStock::withTrashed()->find($historyNull->id);
        $this->assertEquals($this->branchA->id, $repairedHistory->branch_id);
    }

    /**
     * 10. Branch candidate yang tidak ada di tabel branches -> tidak di-apply (UNRESOLVED).
     */
    public function test_branch_candidate_that_does_not_exist_in_branches_table_is_unresolved(): void
    {
        $ghostBranch = Branch::create([
            'nama_cabang' => 'Ghost Branch',
            'kode_cabang' => 'GST',
            'is_active' => true,
        ]);

        $ingredient = Ingredients::create([
            'nama_bahan' => 'Bahan Nonexistent Branch',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 10000,
            'branch_id' => null,
        ]);

        RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $ghostBranch->id,
            'kode' => 'IN-701',
            'qty' => 10,
            'tipe' => 'in',
            'keterangan' => 'Ghost Branch Batch',
        ]);

        // Hapus branch secara fisik untuk mensimulasikan foreign branch hilang di legacy DB
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \Illuminate\Support\Facades\DB::table('branches')->where('id', $ghostBranch->id)->delete();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $this->artisan('inventory:audit-branch-integrity --apply')
            ->expectsOutputToContain('Unresolved      : 1')
            ->expectsOutputToContain('Database changes  : 0')
            ->assertSuccessful();

        $ingredient->refresh();
        $this->assertNull($ingredient->branch_id);
    }

    /**
     * 11. Command tidak mengubah stok.
     * 12. Command tidak mengubah qty history.
     * 13. Command tidak mengubah tipe history.
     * 14. Command tidak mengubah keterangan history.
     */
    public function test_command_does_not_alter_stock_quantities_or_history_metadata(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Sirup Coklat',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 88.5,
            'hpp' => 35000,
            'branch_id' => null,
        ]);

        $history = RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->branchA->id,
            'kode' => 'IN-SPECIAL-88',
            'qty' => 88.5,
            'qty_before' => 10,
            'qty_after' => 98.5,
            'tipe' => 'in',
            'keterangan' => 'Catatan Khusus Restock',
        ]);

        $this->artisan('inventory:audit-branch-integrity --apply')
            ->assertSuccessful();

        $ingredient->refresh();
        $history->refresh();

        // Stok and metadata intact
        $this->assertEquals(88.5, $ingredient->stok);
        $this->assertEquals(35000, $ingredient->hpp);
        $this->assertEquals('Sirup Coklat', $ingredient->nama_bahan);

        $this->assertEquals('IN-SPECIAL-88', $history->kode);
        $this->assertEquals(88.5, $history->qty);
        $this->assertEquals(10, $history->qty_before);
        $this->assertEquals(98.5, $history->qty_after);
        $this->assertEquals('in', $history->tipe);
        $this->assertEquals('Catatan Khusus Restock', $history->keterangan);
    }

    /**
     * 15. Command idempotent: eksekusi kedua dengan --apply menghasilkan 0 changes.
     */
    public function test_command_is_strictly_idempotent(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Kayu Manis Bubuk',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 12,
            'hpp' => 15000,
            'branch_id' => null,
        ]);

        RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->branchA->id,
            'kode' => 'IN-901',
            'qty' => 12,
            'tipe' => 'in',
            'keterangan' => 'Batch Kayu Manis',
        ]);

        $historyNull = RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => null,
            'kode' => 'IN-902',
            'qty' => 5,
            'tipe' => 'in',
            'keterangan' => 'Restock Null',
        ]);

        // Run 1: repairs ingredient (1) + history (1) = 2 changes
        $this->artisan('inventory:audit-branch-integrity --apply')
            ->expectsOutputToContain('Database changes  : 2')
            ->assertSuccessful();

        $ingredient->refresh();
        $historyNull->refresh();
        $this->assertEquals($this->branchA->id, $ingredient->branch_id);
        $this->assertEquals($this->branchA->id, $historyNull->branch_id);

        // Run 2: idempotent, 0 changes
        $this->artisan('inventory:audit-branch-integrity --apply')
            ->expectsOutputToContain('Database changes  : 0')
            ->expectsOutputToContain('Ingredients NULL  : 0')
            ->expectsOutputToContain('RiwayatStock NULL : 0')
            ->assertSuccessful();
    }

    /**
     * 16. Conflict dilaporkan secara jelas pada console output dan section Manual Review.
     */
    public function test_conflicting_history_is_clearly_reported(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'BahanKonflik',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 20,
            'hpp' => 12000,
            'branch_id' => null,
        ]);

        RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->branchA->id,
            'kode' => 'IN-C1',
            'qty' => 10,
            'tipe' => 'in',
            'keterangan' => 'Alpha Batch',
        ]);

        RiwayatStock::create([
            'ingredient_id' => $ingredient->id,
            'branch_id' => $this->branchB->id,
            'kode' => 'IN-C2',
            'qty' => 10,
            'tipe' => 'in',
            'keterangan' => 'Beta Batch',
        ]);

        $this->artisan('inventory:audit-branch-integrity')
            ->expectsOutputToContain('Conflicting stock history branches')
            ->expectsOutputToContain('MANUAL REVIEW REQUIRED FOR INGREDIENTS:')
            ->expectsOutputToContain('Conflicting     : 1')
            ->assertSuccessful();

        $ingredient->refresh();
        $this->assertNull($ingredient->branch_id);
    }
}
