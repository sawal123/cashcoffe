<?php

namespace Tests\Feature;

use App\Livewire\Discount\ApprovalList;
use App\Livewire\Discount\CreateDiscount;
use App\Livewire\Discount\TableDiscount;
use App\Livewire\Order\CreateOrder;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Discount;
use App\Models\DiscountApproval;
use App\Models\Menu;
use App\Models\PaymentMethod;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\PriceTier;
use App\Models\SalesChannel;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DiscountBranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branchA;
    protected Branch $branchB;
    protected User $kasirA;
    protected User $managerA;
    protected User $managerB;
    protected User $superadmin;

    protected ?PaymentMethod $paymentMethod = null;
    protected ?SalesChannel $salesChannel = null;
    protected ?PriceTier $priceTier = null;
    protected ?Menu $menu = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->branchA = Branch::create(['nama_cabang' => 'Branch A', 'kode_cabang' => 'A']);
        $this->branchB = Branch::create(['nama_cabang' => 'Branch B', 'kode_cabang' => 'B']);

        $this->kasirA = User::factory()->create(['branch_id' => $this->branchA->id]);
        $this->kasirA->assignRole('kasir');

        $this->managerA = User::factory()->create(['branch_id' => $this->branchA->id]);
        $this->managerA->assignRole('manager');

        $this->managerB = User::factory()->create(['branch_id' => $this->branchB->id]);
        $this->managerB->assignRole('manager');

        $this->superadmin = User::factory()->create();
        $this->superadmin->assignRole('superadmin');
    }

    // ============================================================
    // Helpers
    // ============================================================

    private function makeDiscount(
        string $kode,
        ?int $branchId = null,
        string $scope = 'global',
        string $type = 'general'
    ): Discount {
        return Discount::create([
            'nama_diskon'       => $kode,
            'type'              => $type,
            'kode_diskon'       => $kode,
            'jenis_diskon'      => 'nominal',
            'nilai_diskon'      => 5000,
            'scope'             => $scope,
            'branch_id'         => $branchId,
            'is_active'         => true,
            'digunakan'         => 0,
            'tanggal_mulai'     => now()->subDay()->toDateString(),
            'tanggal_akhir'     => now()->addDay()->toDateString(),
        ]);
    }

    private function setupPosInfra(): void
    {
        $this->priceTier = PriceTier::first() ?? PriceTier::create(['nama_tier' => 'Regular', 'is_active' => true]);
        $this->salesChannel = SalesChannel::first() ?? SalesChannel::create(['nama_channel' => 'Dine In', 'is_active' => true]);
        $this->paymentMethod = PaymentMethod::first() ?? PaymentMethod::create([
            'nama_metode' => 'Cash',
            'kode_metode' => 'tunai',
            'is_active' => true,
        ]);

        $this->branchA->update(['price_tier_id' => $this->priceTier->id]);
        $this->branchB->update(['price_tier_id' => $this->priceTier->id]);

        $category = Category::create(['nama' => 'Minuman']);
        $this->menu = Menu::create([
            'categories_id' => $category->id,
            'nama_menu'     => 'Kopi Susu',
            'harga'         => 20000,
            'h_pokok'       => 8000,
            'is_active'     => true,
        ]);
    }

    private function cartPayload(): array
    {
        return [
            (string) $this->menu->id => [
                'id'               => $this->menu->id,
                'nama_menu'        => $this->menu->nama_menu,
                'harga'            => $this->menu->harga,
                'gambar'           => '',
                'qty'              => 1,
                'selected_options' => [],
                'catatan'          => '',
            ],
        ];
    }

    private function saveOrderFor(User $user, ?Discount $discount = null): Pesanan
    {
        $component = Livewire::actingAs($user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Tester')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload());

        if ($discount) {
            $component->set('discountId', $discount->id);
        }

        $component->call('saveOrder');

        return Pesanan::latest('id')->firstOrFail();
    }

    // ============================================================
    // 1-5. POS Lookup Kode Discount
    // ============================================================

    public function test_kasir_branch_a_bisa_lookup_discount_branch_a()
    {
        $disc = $this->makeDiscount('DISC-A', $this->branchA->id);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $disc->kode_diskon);

        $component->call('$refresh');

        $this->assertEquals($disc->kode_diskon, $component->viewData('disc')['kode'] ?? null);
    }

    public function test_kasir_branch_a_bisa_lookup_shared_discount()
    {
        $shared = $this->makeDiscount('SHARED', null);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $shared->kode_diskon);

        $component->call('$refresh');

        $this->assertEquals($shared->kode_diskon, $component->viewData('disc')['kode'] ?? null);
    }

    public function test_kasir_branch_a_lookup_discount_branch_b_dianggap_tidak_tersedia()
    {
        $discB = $this->makeDiscount('DISC-B', $this->branchB->id);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $discB->kode_diskon);

        $component->call('$refresh');

        $this->assertNull($component->viewData('disc'));
        $this->assertNull($component->get('discountId'));
    }

    public function test_discount_scope_global_branch_b_tetap_tidak_boleh_untuk_kasir_a()
    {
        // Regression penting: scope=global BUKAN berarti lintas branch
        $discB = $this->makeDiscount('GLOB-B', $this->branchB->id, 'global');

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $discB->kode_diskon);

        $component->call('$refresh');

        $this->assertNull($component->viewData('disc'));
        $this->assertNull($component->get('discountId'));
    }

    public function test_discount_scope_item_category_branch_a_tetap_boleh_untuk_kasir_a()
    {
        $discItem = $this->makeDiscount('ITEM-A', $this->branchA->id, 'item');
        $discCat  = $this->makeDiscount('CAT-A', $this->branchA->id, 'category');

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $discItem->kode_diskon);
        $component->call('$refresh');
        $this->assertEquals($discItem->kode_diskon, $component->viewData('disc')['kode'] ?? null);

        $component->set('discount', $discCat->kode_diskon)->call('$refresh');
        $this->assertEquals($discCat->kode_diskon, $component->viewData('disc')['kode'] ?? null);
    }

    // ============================================================
    // 6. availableDiscounts
    // ============================================================

    public function test_available_discounts_branch_a_berisi_shared_dan_branch_a_tidak_branch_b()
    {
        $shared = $this->makeDiscount('SHARED', null);
        $discA  = $this->makeDiscount('DISC-A', $this->branchA->id);
        $discB  = $this->makeDiscount('DISC-B', $this->branchB->id);

        $component = Livewire::actingAs($this->kasirA)->test(CreateOrder::class);
        $component->call('$refresh');

        $ids = collect($component->viewData('availableDiscountsList'))->pluck('id')->all();

        $this->assertContains($shared->id, $ids);
        $this->assertContains($discA->id, $ids);
        $this->assertNotContains($discB->id, $ids);
    }

    // ============================================================
    // 7. Server Tampering: saveOrder
    // ============================================================

    public function test_save_order_discount_branch_b_ditolak_tanpa_partial_order()
    {
        $this->setupPosInfra();
        $discB = $this->makeDiscount('DISC-B', $this->branchB->id);

        $pesananBefore  = Pesanan::count();
        $itemBefore     = PesananItem::count();

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Tester')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('discountId', $discB->id);

        $component->call('saveOrder')->assertDispatched('showToast');

        $this->assertEquals($pesananBefore, Pesanan::count());
        $this->assertEquals($itemBefore, PesananItem::count());
        $this->assertEquals(0, $discB->fresh()->digunakan);
    }

    // ============================================================
    // 8. Server Tampering: updateOrder
    // ============================================================

    public function test_update_order_discount_branch_b_ditolak_order_tidak_berubah()
    {
        $this->setupPosInfra();
        $discA = $this->makeDiscount('DISC-A', $this->branchA->id);
        $discB = $this->makeDiscount('DISC-B', $this->branchB->id);

        $order = $this->saveOrderFor($this->kasirA, $discA);
        $this->assertEquals(1, $discA->fresh()->digunakan);
        $this->assertEquals($discA->id, $order->fresh()->discount_id);

        $beforeTotal = $order->fresh()->total;

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('orderId', $order->id)
            ->set('nama_costumer', 'Tester Hacked')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('discount_id', $discB->id);

        $component->call('updateOrder')->assertDispatched('showToast');

        $fresh = $order->fresh();
        $this->assertEquals($beforeTotal, $fresh->total);
        $this->assertEquals('Tester', $fresh->nama);
        $this->assertEquals($discA->id, $fresh->discount_id);
        $this->assertEquals(1, $discA->fresh()->digunakan);
        $this->assertEquals(0, $discB->fresh()->digunakan);
    }

    // ============================================================
    // 9-12. Management List / Edit / Delete
    // ============================================================

    public function test_table_discount_manager_branch_a_hanya_lihat_discount_branch_a()
    {
        $shared = $this->makeDiscount('SHARED', null);
        $discA  = $this->makeDiscount('DISC-A', $this->branchA->id);
        $discB  = $this->makeDiscount('DISC-B', $this->branchB->id);

        $component = Livewire::actingAs($this->managerA)->test(TableDiscount::class);

        $ids = $component->viewData('discounts')->getCollection()->pluck('id')->all();

        $this->assertContains($discA->id, $ids);
        $this->assertNotContains($discB->id, $ids);
        $this->assertNotContains($shared->id, $ids);
    }

    public function test_branch_a_tidak_bisa_edit_discount_branch_b()
    {
        $discB = $this->makeDiscount('DISC-B', $this->branchB->id);

        $this->actingAs($this->managerA)
            ->get('/discount/' . base64_encode($discB->id) . '/edit')
            ->assertForbidden();

        Livewire::actingAs($this->managerA)
            ->test(CreateDiscount::class)
            ->set('nama_diskon', 'Hacked B')
            ->call('update', $discB->id)
            ->assertForbidden();

        $this->assertEquals('DISC-B', $discB->fresh()->nama_diskon);
    }

    public function test_branch_a_tidak_bisa_delete_discount_branch_b()
    {
        $discB = $this->makeDiscount('DISC-B', $this->branchB->id);

        Livewire::actingAs($this->managerA)
            ->test(TableDiscount::class)
            ->call('delete', base64_encode($discB->id))
            ->assertForbidden();

        $this->assertFalse(Discount::withTrashed()->findOrFail($discB->id)->trashed());
    }

    public function test_branch_a_tidak_bisa_edit_dan_delete_shared_discount()
    {
        $shared = $this->makeDiscount('SHARED', null);

        $this->actingAs($this->managerA)
            ->get('/discount/' . base64_encode($shared->id) . '/edit')
            ->assertForbidden();

        Livewire::actingAs($this->managerA)
            ->test(CreateDiscount::class)
            ->set('nama_diskon', 'Hacked Shared')
            ->call('update', $shared->id)
            ->assertForbidden();

        Livewire::actingAs($this->managerA)
            ->test(TableDiscount::class)
            ->call('delete', base64_encode($shared->id))
            ->assertForbidden();

        $this->assertFalse(Discount::withTrashed()->findOrFail($shared->id)->trashed());
        $this->assertEquals('SHARED', $shared->fresh()->nama_diskon);
    }

    // ============================================================
    // 13-15. Create Discount Branch Forcing & Superadmin
    // ============================================================

    public function test_non_superadmin_create_discount_tampered_branch_b_tersimpan_branch_a()
    {
        Livewire::actingAs($this->managerA)
            ->test(CreateDiscount::class)
            ->set('nama_diskon', 'Tampered B')
            ->set('jenis_diskon', 'nominal')
            ->set('nilai_diskon', 500)
            ->set('is_active', 1)
            ->set('member_only', false)
            ->set('scope', 'global')
            ->set('branch_id', $this->branchB->id)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('discounts', [
            'nama_diskon' => 'Tampered B',
            'branch_id'   => $this->branchA->id,
        ]);
    }

    public function test_non_superadmin_create_discount_branch_null_tersimpan_branch_a()
    {
        Livewire::actingAs($this->managerA)
            ->test(CreateDiscount::class)
            ->set('nama_diskon', 'Forced Branch')
            ->set('jenis_diskon', 'nominal')
            ->set('nilai_diskon', 500)
            ->set('is_active', 1)
            ->set('member_only', false)
            ->set('scope', 'global')
            ->set('branch_id', null)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('discounts', [
            'nama_diskon' => 'Forced Branch',
            'branch_id'   => $this->branchA->id,
        ]);
    }

    public function test_superadmin_dapat_membuat_shared_dan_mengelola_semua_branch()
    {
        // Superadmin membuat shared discount (branch_id NULL)
        Livewire::actingAs($this->superadmin)
            ->test(CreateDiscount::class)
            ->set('nama_diskon', 'Super Shared')
            ->set('jenis_diskon', 'nominal')
            ->set('nilai_diskon', 1000)
            ->set('is_active', 1)
            ->set('member_only', false)
            ->set('scope', 'global')
            ->set('branch_id', null)
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('discounts', ['nama_diskon' => 'Super Shared', 'branch_id' => null]);

        $shared = Discount::where('nama_diskon', 'Super Shared')->firstOrFail();
        $discA  = $this->makeDiscount('DISC-A', $this->branchA->id);
        $discB  = $this->makeDiscount('DISC-B', $this->branchB->id);

        // Superadmin melihat semua discount di management list
        $component = Livewire::actingAs($this->superadmin)->test(TableDiscount::class);
        $ids = $component->viewData('discounts')->getCollection()->pluck('id')->all();

        $this->assertContains($shared->id, $ids);
        $this->assertContains($discA->id, $ids);
        $this->assertContains($discB->id, $ids);

        // Superadmin bisa edit discount branch B (melalui mount, seperti flow UI asli)
        Livewire::actingAs($this->superadmin)
            ->test(CreateDiscount::class, ['id' => base64_encode($discB->id)])
            ->set('nama_diskon', 'DISC-B Edited')
            ->call('update', $discB->id)
            ->assertHasNoErrors();

        $this->assertEquals('DISC-B Edited', $discB->fresh()->nama_diskon);
        $this->assertEquals($this->branchB->id, $discB->fresh()->branch_id);
    }

    // ============================================================
    // 16-20. Approval Branch Isolation
    // ============================================================

    private function makeApproval(User $kasir, ?Discount $discount = null): DiscountApproval
    {
        $discount = $discount ?? $this->makeDiscount('APPR-' . strtoupper(substr(uniqid(), -4)));

        return DiscountApproval::create([
            'discount_id' => $discount->id,
            'status'      => 'pending',
            'kasir_id'    => $kasir->id,
        ]);
    }

    public function test_approver_branch_a_hanya_lihat_approval_kasir_branch_a()
    {
        $approvalA = $this->makeApproval($this->kasirA);
        $approvalB = $this->makeApproval($this->managerB);

        $component = Livewire::actingAs($this->managerA)->test(ApprovalList::class);

        $ids = $component->viewData('approvals')->getCollection()->pluck('id')->all();

        $this->assertContains($approvalA->id, $ids);
        $this->assertNotContains($approvalB->id, $ids);
    }

    public function test_approver_branch_a_tidak_bisa_proses_approval_branch_b()
    {
        $approvalB = $this->makeApproval($this->managerB);

        Livewire::actingAs($this->managerA)
            ->test(ApprovalList::class)
            ->set('selectedApprovalId', $approvalB->id)
            ->set('actionType', 'approve')
            ->set('keterangan', 'ok')
            ->call('submitAction')
            ->assertForbidden();

        $this->assertEquals('pending', $approvalB->fresh()->status);
    }

    public function test_user_branch_a_dengan_approve_all_discount_tetap_tidak_boleh_approve_branch_b()
    {
        // Manager A punya permission `approve all discount`, tapi itu BUKAN akses semua cabang
        $this->assertTrue($this->managerA->can('approve all discount'));

        $approvalB = $this->makeApproval($this->managerB);

        Livewire::actingAs($this->managerA)
            ->test(ApprovalList::class)
            ->set('selectedApprovalId', $approvalB->id)
            ->set('actionType', 'approve')
            ->set('keterangan', 'ok')
            ->call('submitAction')
            ->assertForbidden();

        $this->assertEquals('pending', $approvalB->fresh()->status);
    }

    public function test_superadmin_bisa_lihat_dan_proses_approval_semua_branch()
    {
        $approvalA = $this->makeApproval($this->kasirA);
        $approvalB = $this->makeApproval($this->managerB);

        $component = Livewire::actingAs($this->superadmin)->test(ApprovalList::class);
        $ids = $component->viewData('approvals')->getCollection()->pluck('id')->all();
        $this->assertContains($approvalA->id, $ids);
        $this->assertContains($approvalB->id, $ids);

        Livewire::actingAs($this->superadmin)
            ->test(ApprovalList::class)
            ->set('selectedApprovalId', $approvalA->id)
            ->set('actionType', 'approve')
            ->set('keterangan', 'ok')
            ->call('submitAction')
            ->assertDispatched('close-modal');

        Livewire::actingAs($this->superadmin)
            ->test(ApprovalList::class)
            ->set('selectedApprovalId', $approvalB->id)
            ->set('actionType', 'approve')
            ->set('keterangan', 'ok')
            ->call('submitAction')
            ->assertDispatched('close-modal');

        $this->assertEquals('approved', $approvalA->fresh()->status);
        $this->assertEquals('approved', $approvalB->fresh()->status);
    }

    public function test_shared_discount_approval_hanya_bisa_diproses_approver_branch_kasir()
    {
        $shared = $this->makeDiscount('SHARED', null);
        $approval = $this->makeApproval($this->kasirA, $shared);

        // Approver branch B TIDAK boleh memproses request kasir branch A
        Livewire::actingAs($this->managerB)
            ->test(ApprovalList::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('actionType', 'approve')
            ->set('keterangan', 'ok')
            ->call('submitAction')
            ->assertForbidden();

        $this->assertEquals('pending', $approval->fresh()->status);

        // Approver branch A boleh memproses
        Livewire::actingAs($this->managerA)
            ->test(ApprovalList::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('actionType', 'approve')
            ->set('keterangan', 'ok')
            ->call('submitAction')
            ->assertDispatched('close-modal');

        $this->assertEquals('approved', $approval->fresh()->status);
    }
}
