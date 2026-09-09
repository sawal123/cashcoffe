<?php

namespace Tests\Feature;

use App\Livewire\Stock\StockDapurCreate;
use App\Models\Branch;
use App\Models\Ingredients;
use App\Models\RiwayatStock;
use App\Models\SatuanBahan;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StockDapurCreateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected SatuanBahan $satuanKg;
    protected SatuanBahan $satuanGram;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('superadmin');

        $this->satuanKg = SatuanBahan::create(['nama_satuan' => 'Kg']);
        $this->satuanGram = SatuanBahan::create(['nama_satuan' => 'Gram']);
    }

    /**
     * 1. Encoded route ID membuka ingredient yang benar.
     * 2. nama_bahan ter-load.
     * 3. stok ter-load.
     * 4. hpp ter-load.
     * 5. satuan_id ter-load.
     * 6. Form menggunakan wire:submit.prevent="update" tanpa argumen Base64.
     */
    public function test_encoded_route_id_opens_and_loads_all_ingredient_fields_correctly(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Biji Kopi Arabika',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 45,
            'hpp' => 85000,
        ]);

        $encodedId = base64_encode($ingredient->id);

        Livewire::actingAs($this->user)
            ->test(StockDapurCreate::class, ['stockId' => $encodedId])
            ->assertSet('ingredient_id', $ingredient->id)
            ->assertSet('isEdit', true)
            ->assertSet('nama_bahan', 'Biji Kopi Arabika')
            ->assertSet('stok', 45)
            ->assertSet('hpp', 85000)
            ->assertSet('satuan_id', $this->satuanKg->id)
            ->assertSeeHtml('wire:submit.prevent="update"')
            ->assertDontSeeHtml('update(')
            ->assertSee('Update');
    }

    /**
     * 7. Perubahan nama tersimpan.
     * 8. Perubahan stok tersimpan.
     * 9. Perubahan HPP tersimpan.
     * 10. Perubahan satuan tersimpan.
     * 11. Klik/submit Update benar-benar menjalankan update.
     */
    public function test_update_persists_changes_for_nama_stok_hpp_satuan(): void
    {
        $ingredient = Ingredients::create([
            'nama_bahan' => 'Gula Pasir Asli',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 20,
            'hpp' => 15000,
        ]);

        $encodedId = base64_encode($ingredient->id);

        Livewire::actingAs($this->user)
            ->test(StockDapurCreate::class, ['stockId' => $encodedId])
            ->set('nama_bahan', 'Gula Pasir Premium')
            ->set('stok', 35)
            ->set('hpp', 18000)
            ->set('satuan_id', $this->satuanGram->id)
            ->call('update')
            ->assertDispatched('showToast', type: 'success', message: 'Bahan berhasil diupdate!');

        $ingredient->refresh();

        $this->assertEquals('Gula Pasir Premium', $ingredient->nama_bahan);
        $this->assertEquals(35, $ingredient->stok);
        $this->assertEquals(18000, $ingredient->hpp);
        $this->assertEquals($this->satuanGram->id, $ingredient->satuan_id);
    }

    /**
     * Invalid/malformed Base64 gagal secara aman (404).
     */
    public function test_invalid_malformed_base64_fails_safely(): void
    {
        Livewire::actingAs($this->user)
            ->test(StockDapurCreate::class, ['stockId' => 'invalid!@#base64'])
            ->assertStatus(404);

        Livewire::actingAs($this->user)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode('abc')])
            ->assertStatus(404);

        Livewire::actingAs($this->user)
            ->test(StockDapurCreate::class, ['stockId' => base64_encode('0')])
            ->assertStatus(404);
    }

    /**
     * Nonexistent ingredient ID gagal secara aman (404).
     */
    public function test_nonexistent_ingredient_fails_safely(): void
    {
        $nonExistentEncoded = base64_encode('99999999');

        Livewire::actingAs($this->user)
            ->test(StockDapurCreate::class, ['stockId' => $nonExistentEncoded])
            ->assertStatus(404);
    }

    /**
     * Isolasi Branch BelongsToBranch tidak dapat dibypass via Base64 ID.
     */
    public function test_branch_isolation_cannot_be_bypassed_via_encoded_id(): void
    {
        $branchA = Branch::create(['nama_cabang' => 'Cabang A', 'kode_cabang' => 'CBA', 'is_active' => true]);
        $branchB = Branch::create(['nama_cabang' => 'Cabang B', 'kode_cabang' => 'CBB', 'is_active' => true]);

        // User kasir/manager di Cabang A (bukan superadmin)
        $userCabangA = User::factory()->create([
            'branch_id' => $branchA->id,
        ]);
        $userCabangA->assignRole('manager');

        // Bahan baku di Cabang B
        $ingredientBranchB = Ingredients::create([
            'nama_bahan' => 'Bahan Cabang B',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 5000,
            'branch_id' => $branchB->id,
        ]);

        $encodedIdB = base64_encode($ingredientBranchB->id);

        Livewire::actingAs($userCabangA)
            ->test(StockDapurCreate::class, ['stockId' => $encodedIdB])
            ->assertStatus(404);
    }

    /**
     * Create flow simpan() tetap bekerja normal.
     */
    public function test_create_flow_simpan_still_works(): void
    {
        Livewire::actingAs($this->user)
            ->test(StockDapurCreate::class)
            ->assertSet('isEdit', false)
            ->assertSet('ingredient_id', null)
            ->assertSeeHtml('wire:submit.prevent="simpan"')
            ->assertSee('Simpan')
            ->set('nama_bahan', 'Susu UHT Fresh')
            ->set('stok', 50)
            ->set('hpp', 18500)
            ->set('satuan_id', $this->satuanKg->id)
            ->call('simpan')
            ->assertDispatched('showToast', type: 'success', message: 'Bahan berhasil disimpan!')
            ->assertSet('nama_bahan', null)
            ->assertSet('stok', null)
            ->assertSet('hpp', null);

        $this->assertDatabaseHas('ingredients', [
            'nama_bahan' => 'Susu UHT Fresh',
            'stok' => 50,
            'hpp' => 18500,
            'satuan_id' => $this->satuanKg->id,
        ]);

        $created = Ingredients::where('nama_bahan', 'Susu UHT Fresh')->first();
        $this->assertNotNull($created);

        $this->assertDatabaseHas('riwayat_stocks', [
            'ingredient_id' => $created->id,
            'qty' => 50,
            'tipe' => 'in',
            'keterangan' => 'Stok awal',
        ]);
    }
}
