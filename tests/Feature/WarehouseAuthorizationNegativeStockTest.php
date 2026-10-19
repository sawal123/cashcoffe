<?php

namespace Tests\Feature;

use App\Livewire\Gudang\CreateGudang;
use App\Livewire\Gudang\TableGudang;
use App\Livewire\RiwayatGudang\TableRiwayat;
use App\Models\Branch;
use App\Models\Gudang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WarehouseAuthorizationNegativeStockTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;

    protected Branch $branchB;

    protected User $adminA;

    protected User $adminB;

    protected User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->branchA = Branch::create(['nama_cabang' => 'Cabang A', 'is_active' => true]);
        $this->branchB = Branch::create(['nama_cabang' => 'Cabang B', 'is_active' => true]);

        $this->adminA = User::factory()->create(['branch_id' => $this->branchA->id]);
        $this->adminA->assignRole('admin');

        $this->adminB = User::factory()->create(['branch_id' => $this->branchB->id]);
        $this->adminB->assignRole('admin');

        $this->superadmin = User::factory()->create(['branch_id' => null]);
        $this->superadmin->assignRole('superadmin');
    }

    private function stock(string $name, float $quantity, int $branchId): Gudang
    {
        return Gudang::create([
            'branch_id' => $branchId,
            'nama_bahan' => $name,
            'satuan' => 'kg',
            'stok' => $quantity,
            'harga_satuan' => 100,
            'minimum_stok' => 1,
        ]);
    }

    private function transact(User $actor, string $name, string $type, float $quantity, float $price = 100): void
    {
        Livewire::actingAs($actor)
            ->test(CreateGudang::class)
            ->set('nama_bahan', $name)
            ->set('jumlah_masuk', $quantity)
            ->set('satuan', 'kg')
            ->set('harga_satuan', $price)
            ->set('tipe', $type)
            ->call('simpan')
            ->assertHasNoErrors();
    }

    public function test_normal_stock_out_preserves_transaction_balances(): void
    {
        $gudang = $this->stock('Kopi Normal', 20, $this->branchA->id);

        $this->transact($this->adminA, 'Kopi Normal', 'keluar', 5);

        $history = $gudang->riwayats()->latest('id')->firstOrFail();
        $gudang->refresh();

        $this->assertSame(20.0, (float) $history->stok_sebelum);
        $this->assertSame(15.0, (float) $history->stok_sesudah);
        $this->assertSame(15.0, (float) $gudang->stok);
    }

    public function test_stock_out_can_create_a_negative_balance(): void
    {
        $gudang = $this->stock('Kopi Negatif', 10, $this->branchA->id);

        $this->transact($this->adminA, 'Kopi Negatif', 'keluar', 15);

        $history = $gudang->riwayats()->latest('id')->firstOrFail();
        $gudang->refresh();

        $this->assertSame(10.0, (float) $history->stok_sebelum);
        $this->assertSame(-5.0, (float) $history->stok_sesudah);
        $this->assertSame(-5.0, (float) $gudang->stok);

        Livewire::actingAs($this->adminA)
            ->test(TableGudang::class)
            ->assertSee('Stok Negatif')
            ->assertSee('-5,00');
    }

    public function test_restock_after_negative_balance_is_calculated_from_actual_balance(): void
    {
        $gudang = $this->stock('Kopi Restock', -5, $this->branchA->id);

        $this->transact($this->adminA, 'Kopi Restock', 'masuk', 12, 200);

        $history = $gudang->riwayats()->latest('id')->firstOrFail();
        $gudang->refresh();

        $this->assertSame(-5.0, (float) $history->stok_sebelum);
        $this->assertSame(7.0, (float) $history->stok_sesudah);
        $this->assertSame(7.0, (float) $gudang->stok);
        $this->assertTrue(is_finite((float) $gudang->harga_satuan));
    }

    public function test_editing_a_stock_out_rebuilds_negative_balance(): void
    {
        $gudang = $this->stock('Kopi Edit', 10, $this->branchA->id);
        $this->transact($this->adminA, 'Kopi Edit', 'keluar', 15);
        $history = $gudang->riwayats()->latest('id')->firstOrFail();

        Livewire::actingAs($this->adminA)
            ->test(CreateGudang::class, ['riwayatId' => base64_encode($history->id)])
            ->set('jumlah_masuk', 18)
            ->set('branch_id', $this->branchB->id)
            ->set('tipe', 'masuk')
            ->call('update')
            ->assertHasNoErrors();

        $history->refresh();
        $gudang->refresh();

        $this->assertSame($gudang->id, (int) $history->gudang_id);
        $this->assertSame('keluar', $history->tipe);
        $this->assertSame(10.0, (float) $history->stok_sebelum);
        $this->assertSame(-8.0, (float) $history->stok_sesudah);
        $this->assertSame(-8.0, (float) $gudang->stok);
    }

    public function test_multiple_stock_transactions_keep_history_chain_consistent(): void
    {
        $gudang = $this->stock('Kopi Berantai', 10, $this->branchA->id);

        $this->transact($this->adminA, 'Kopi Berantai', 'keluar', 15);
        $this->transact($this->adminA, 'Kopi Berantai', 'masuk', 8);
        $this->transact($this->adminA, 'Kopi Berantai', 'keluar', 7);

        $histories = $gudang->riwayats()->orderBy('id')->get();
        $gudang->refresh();

        $this->assertSame([10.0, -5.0, 3.0], $histories->pluck('stok_sebelum')->map(fn ($value) => (float) $value)->all());
        $this->assertSame([-5.0, 3.0, -4.0], $histories->pluck('stok_sesudah')->map(fn ($value) => (float) $value)->all());
        $this->assertSame(-4.0, (float) $gudang->stok);
    }

    public function test_admin_can_edit_own_history_but_cannot_delete_or_cross_branch_mutate(): void
    {
        $ownGudang = $this->stock('Kopi Admin A', 20, $this->branchA->id);
        $this->transact($this->adminA, 'Kopi Admin A', 'keluar', 5);
        $ownHistory = $ownGudang->riwayats()->latest('id')->firstOrFail();

        $otherGudang = $this->stock('Kopi Admin B', 20, $this->branchB->id);
        $this->transact($this->adminB, 'Kopi Admin B', 'keluar', 5);
        $otherHistory = $otherGudang->riwayats()->latest('id')->firstOrFail();

        Livewire::actingAs($this->adminA)
            ->test(CreateGudang::class, ['riwayatId' => base64_encode($ownHistory->id)])
            ->set('jumlah_masuk', 6)
            ->call('update')
            ->assertHasNoErrors();

        Livewire::actingAs($this->adminA)
            ->test(CreateGudang::class, ['gudangId' => base64_encode($ownGudang->id)])
            ->set('nama_bahan', 'Kopi Admin A Updated')
            ->call('updateBahan')
            ->assertHasNoErrors();

        Livewire::actingAs($this->adminA)
            ->test(TableRiwayat::class)
            ->assertSee('Kopi Admin A Updated')
            ->assertDontSee('Kopi Admin B')
            ->assertDontSee('Hapus Data Riwayat Ini?')
            ->call('deleteRiwayat', base64_encode($ownHistory->id))
            ->assertForbidden();

        Livewire::actingAs($this->adminA)
            ->test(TableRiwayat::class)
            ->call('deleteRiwayat', base64_encode($otherHistory->id))
            ->assertForbidden();

        Livewire::actingAs($this->adminA)
            ->test(TableGudang::class)
            ->assertDontSee('Hapus Data Bahan Ini?')
            ->call('deleteGudang', base64_encode($ownGudang->id))
            ->assertForbidden();

        $this->actingAs($this->adminA)
            ->get('/gudang/riwayat/'.base64_encode($otherHistory->id).'/edit')
            ->assertNotFound();
        $this->assertDatabaseHas('gudang_riwayats', ['id' => $ownHistory->id]);
        $this->assertDatabaseHas('gudangs', ['id' => $ownGudang->id]);
    }

    public function test_superadmin_delete_rebuilds_following_history_and_stock(): void
    {
        $gudang = $this->stock('Kopi Superadmin', 10, $this->branchA->id);
        $this->transact($this->adminA, 'Kopi Superadmin', 'keluar', 15);
        $this->transact($this->adminA, 'Kopi Superadmin', 'masuk', 8);
        $firstHistory = $gudang->riwayats()->orderBy('id')->firstOrFail();

        Livewire::actingAs($this->superadmin)
            ->test(TableRiwayat::class)
            ->call('deleteRiwayat', base64_encode($firstHistory->id))
            ->assertHasNoErrors();

        $remaining = $gudang->riwayats()->firstOrFail();
        $gudang->refresh();

        $this->assertDatabaseMissing('gudang_riwayats', ['id' => $firstHistory->id]);
        $this->assertSame(10.0, (float) $remaining->stok_sebelum);
        $this->assertSame(18.0, (float) $remaining->stok_sesudah);
        $this->assertSame(18.0, (float) $gudang->stok);
    }

    public function test_superadmin_can_view_history_across_branches(): void
    {
        $this->stock('Kopi Cabang B', 10, $this->branchB->id);
        $this->transact($this->adminB, 'Kopi Cabang B', 'keluar', 3);

        Livewire::actingAs($this->superadmin)
            ->test(TableRiwayat::class)
            ->assertSee('Kopi Cabang B');
    }
}
