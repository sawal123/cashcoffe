<?php

namespace Tests\Feature;

use App\Livewire\Gudang\CreateGudang;
use App\Livewire\Gudang\TableGudang;
use App\Livewire\Payroll\GenerasiGaji;
use App\Livewire\RiwayatGudang\TableRiwayat;
use App\Livewire\User\CreateUser;
use App\Models\Branch;
use App\Models\Gudang;
use App\Models\GudangRiwayat;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminBranchPayrollWarehouseTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;

    protected Branch $branchB;

    protected User $adminA;

    protected User $superadmin;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin', 'manager', 'karyawan', 'kasir'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->branchA = Branch::create(['nama_cabang' => 'Cabang A', 'is_active' => true]);
        $this->branchB = Branch::create(['nama_cabang' => 'Cabang B', 'is_active' => true]);

        $this->adminA = User::factory()->create(['branch_id' => $this->branchA->id]);
        $this->adminA->assignRole('admin');

        $this->superadmin = User::factory()->create(['branch_id' => null]);
        $this->superadmin->assignRole('superadmin');

        $this->manager = User::factory()->create(['branch_id' => $this->branchA->id]);
        $this->manager->assignRole('manager');
    }

    private function stock(string $name, ?int $branchId): Gudang
    {
        return Gudang::create([
            'branch_id' => $branchId,
            'nama_bahan' => $name,
            'satuan' => 'kg',
            'stok' => 10,
            'harga_satuan' => 12000,
            'minimum_stok' => 1,
        ]);
    }

    public function test_superadmin_user_has_no_branch_position_or_payroll_fields(): void
    {
        Livewire::actingAs($this->superadmin)
            ->test(CreateUser::class)
            ->set('name', 'Super Admin Tanpa Cabang')
            ->set('email', 'super-no-branch@example.test')
            ->set('password', 'password123')
            ->set('role_selected', ['superadmin'])
            ->set('branch_id', $this->branchB->id)
            ->set('gaji_pokok', 99000)
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'super-no-branch@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('superadmin'));
        $this->assertNull($user->branch_id);
        $this->assertNull($user->jabatan_id);
        $this->assertNull($user->gaji_pokok);
        $this->assertNull($user->tunjangan_harian);
        $this->assertSame(0, $user->hak_cuti);
    }

    public function test_admin_sees_only_own_branch_payroll_and_cannot_open_other_details(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 12:00:00'));
        try {
            $employeeA = User::factory()->create(['name' => 'Payroll Employee A', 'branch_id' => $this->branchA->id]);
            $employeeB = User::factory()->create(['name' => 'Payroll Employee B', 'branch_id' => $this->branchB->id]);
            $employeeA->assignRole('karyawan');
            $employeeB->assignRole('karyawan');

            [$start, $end] = (new PayrollService)->getCutoffPeriod(2026, 9);
            foreach ([$employeeA, $employeeB] as $employee) {
                Payroll::create([
                    'user_id' => $employee->id,
                    'periode_mulai' => $start->toDateString(),
                    'periode_selesai' => $end->toDateString(),
                    'gaji_pokok' => 1000000,
                    'gaji_bersih' => 1000000,
                ]);
            }

            Livewire::actingAs($this->adminA)
                ->test(GenerasiGaji::class)
                ->assertSee('Payroll Employee A')
                ->assertDontSee('Payroll Employee B');

            $other = Payroll::where('user_id', $employeeB->id)->firstOrFail();
            Livewire::actingAs($this->adminA)
                ->test(GenerasiGaji::class)
                ->call('showDoubleShiftDetails', $other->id)
                ->assertNotFound();

            $this->actingAs($this->adminA)->get('/payroll/generasi')->assertOk();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_admin_mass_payroll_only_generates_for_own_branch(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 12:00:00'));
        try {
            $employeeA = User::factory()->create([
                'name' => 'Mass Payroll Employee A',
                'branch_id' => $this->branchA->id,
                'gaji_pokok' => 1000000,
            ]);
            $employeeB = User::factory()->create([
                'name' => 'Mass Payroll Employee B',
                'branch_id' => $this->branchB->id,
                'gaji_pokok' => 1000000,
            ]);
            $employeeA->assignRole('karyawan');
            $employeeB->assignRole('karyawan');

            Livewire::actingAs($this->adminA)
                ->test(GenerasiGaji::class)
                ->call('hitungGajiMassal')
                ->assertHasNoErrors();

            [$start, $end] = (new PayrollService)->getCutoffPeriod(2026, 9);

            $periodPayrolls = fn (int $userId) => Payroll::query()
                ->where('user_id', $userId)
                ->whereDate('periode_mulai', $start->toDateString())
                ->whereDate('periode_selesai', $end->toDateString());

            $this->assertSame(1, $periodPayrolls($employeeA->id)->count());
            $this->assertSame(0, $periodPayrolls($employeeB->id)->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_admin_sidebar_shows_branch_warehouse_navigation(): void
    {
        $response = $this->actingAs($this->adminA)->get('/dashboard');

        $response->assertOk()
            ->assertSee('href="/gudang"', false)
            ->assertSee('href="/riwayat-gudang"', false)
            ->assertDontSee('href="/menu-cabang"', false);
    }

    public function test_manager_sidebar_hides_order_and_shows_payroll_warehouse_navigation(): void
    {
        $response = $this->actingAs($this->manager)->get('/dashboard');

        $response->assertOk()
            ->assertSee('href="/payroll/generasi"', false)
            ->assertSee('href="/gudang"', false)
            ->assertSee('href="/riwayat-gudang"', false)
            ->assertDontSee('href="/order"', false)
            ->assertDontSee('href="/order/create"', false)
            ->assertDontSee('href="/member"', false)
            ->assertDontSee('href="/omset"', false)
            ->assertDontSee('Menu &amp; Order', false);

        $this->actingAs($this->manager)->get('/payroll/generasi')->assertOk();
        $this->actingAs($this->manager)->get('/gudang')->assertOk();
        $this->actingAs($this->manager)->get('/riwayat-gudang')->assertOk();
    }

    public function test_admin_can_create_only_own_branch_stock_even_with_forged_branch_property(): void
    {
        Livewire::actingAs($this->adminA)
            ->test(CreateGudang::class)
            ->set('branch_id', $this->branchB->id)
            ->set('nama_bahan', 'Bahan Eksklusif A')
            ->set('jumlah_masuk', 3)
            ->set('satuan', 'kg')
            ->set('harga_satuan', 2000)
            ->call('simpan')
            ->assertHasNoErrors();

        $item = Gudang::where('nama_bahan', 'Bahan Eksklusif A')->firstOrFail();
        $this->assertSame($this->branchA->id, (int) $item->branch_id);
        $this->assertSame(1, $item->riwayats()->count());
    }

    public function test_admin_cannot_read_or_mutate_other_branch_stock_or_history(): void
    {
        $own = $this->stock('Stok Lokal A', $this->branchA->id);
        $other = $this->stock('Stok Rahasia B', $this->branchB->id);
        $legacy = $this->stock('Stok Lama Global', null);
        $historyB = GudangRiwayat::create([
            'gudang_id' => $other->id,
            'tipe' => 'masuk',
            'jumlah' => 10,
            'stok_sebelum' => 0,
            'stok_sesudah' => 10,
            'user_id' => $this->superadmin->id,
        ]);

        Livewire::actingAs($this->adminA)
            ->test(TableGudang::class)
            ->assertSee('Stok Lokal A')
            ->assertDontSee('Stok Rahasia B')
            ->assertDontSee('Stok Lama Global')
            ->call('deleteGudang', base64_encode($other->id))
            ->assertForbidden();

        $this->assertDatabaseHas('gudangs', ['id' => $other->id]);
        $this->actingAs($this->adminA)->get('/gudang/'.base64_encode($other->id).'/edit')->assertNotFound();
        $this->actingAs($this->adminA)->get('/gudang/riwayat/'.base64_encode($historyB->id).'/edit')->assertNotFound();

        Livewire::actingAs($this->adminA)
            ->test(TableRiwayat::class)
            ->assertDontSee('Stok Rahasia B')
            ->call('deleteRiwayat', base64_encode($historyB->id))
            ->assertForbidden();

        $this->assertDatabaseHas('gudang_riwayats', ['id' => $historyB->id]);
        $this->assertDatabaseHas('gudangs', ['id' => $legacy->id]);
        $this->assertDatabaseHas('gudangs', ['id' => $own->id]);
    }

    public function test_superadmin_can_assign_legacy_stock_to_a_branch_without_deleting_it(): void
    {
        $legacy = $this->stock('Stok Legacy', null);

        Livewire::actingAs($this->superadmin)
            ->test(CreateGudang::class, ['gudangId' => base64_encode($legacy->id)])
            ->set('branch_id', $this->branchB->id)
            ->call('updateBahan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gudangs', [
            'id' => $legacy->id,
            'branch_id' => $this->branchB->id,
            'stok' => 10,
        ]);

        Livewire::actingAs($this->adminA)
            ->test(TableGudang::class)
            ->assertDontSee('Stok Legacy');
    }

    public function test_admin_without_branch_is_denied_payroll_and_warehouse(): void
    {
        $unassigned = User::factory()->create(['branch_id' => null]);
        $unassigned->assignRole('admin');

        $this->actingAs($unassigned)->get('/payroll/generasi')->assertForbidden();
        $this->actingAs($unassigned)->get('/gudang')->assertForbidden();
        $this->actingAs($unassigned)->get('/riwayat-gudang')->assertForbidden();
        $this->actingAs($unassigned)->get('/user')->assertForbidden();
    }
}
