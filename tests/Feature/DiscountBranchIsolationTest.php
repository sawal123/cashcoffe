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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

    /**
     * Buat pesanan langsung ke DB (simulasi historical/legacy data) dengan
     * branch_id eksplisit — branch_id tidak ada di $fillable Pesanan.
     */
    private function createPesananForBranch(?Discount $discount, ?int $branchId, string $status = 'diproses'): Pesanan
    {
        $pesanan = new Pesanan([
            'kode'           => 'LEG-' . strtoupper(Str::random(6)),
            'discount_id'    => $discount?->id,
            'discount_value' => $discount ? 5000 : 0,
            'status'         => $status,
            'total'          => 20000,
            'total_profit'   => 12000,
        ]);
        $pesanan->branch_id = $branchId;
        $pesanan->save();

        return $pesanan;
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

    // ============================================================
    // 21-29. Blocker Tambahan: search leak, approval tampering,
    // malformed/legacy data, non-superadmin tanpa branch
    // ============================================================

    public function test_search_table_discount_tidak_menembus_branch_filter_karena_or_where()
    {
        // Keduanya jenis_diskon = nominal; manager A search 'nominal' hanya lihat branch A
        $discA = $this->makeDiscount('DISC-A', $this->branchA->id);
        $discB = $this->makeDiscount('DISC-B', $this->branchB->id);

        $component = Livewire::actingAs($this->managerA)
            ->test(TableDiscount::class)
            ->set('search', 'nominal');

        $ids = $component->viewData('discounts')->getCollection()->pluck('id')->all();

        $this->assertContains($discA->id, $ids);
        $this->assertNotContains($discB->id, $ids);
    }

    public function test_kasir_a_request_admin_approval_discount_branch_b_tidak_membuat_approval()
    {
        Http::fake();

        $discB = $this->makeDiscount('DISC-B', $this->branchB->id);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $discB->kode_diskon);

        $component->call('requestAdminApproval');

        $this->assertEquals(0, DiscountApproval::count());
        $this->assertNull($component->get('approvalRequestId'));
        $this->assertFalse($component->get('isWaitingApproval'));
    }

    public function test_kasir_a_request_admin_approval_shared_discount_boleh()
    {
        Http::fake();

        $shared = $this->makeDiscount('SHARED', null);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $shared->kode_diskon);

        $component->call('requestAdminApproval');

        $approval = DiscountApproval::latest('id')->first();
        $this->assertNotNull($approval);
        $this->assertEquals($shared->id, $approval->discount_id);
        $this->assertEquals($this->kasirA->id, $approval->kasir_id);
        $this->assertEquals($approval->id, $component->get('approvalRequestId'));
        $this->assertTrue($component->get('isWaitingApproval'));
    }

    public function test_approval_kasir_branch_a_tapi_discount_branch_b_tidak_terlihat_dan_ditolak()
    {
        // Data malformed/legacy: requester = kasir A, tapi discount = branch B
        $discB = $this->makeDiscount('DISC-B', $this->branchB->id);
        $approval = DiscountApproval::create([
            'discount_id' => $discB->id,
            'status'      => 'pending',
            'kasir_id'    => $this->kasirA->id,
        ]);

        // Manager A tidak melihat
        $component = Livewire::actingAs($this->managerA)->test(ApprovalList::class);
        $ids = $component->viewData('approvals')->getCollection()->pluck('id')->all();
        $this->assertNotContains($approval->id, $ids);

        // Direct submitAction => 403
        Livewire::actingAs($this->managerA)
            ->test(ApprovalList::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('actionType', 'approve')
            ->set('keterangan', 'ok')
            ->call('submitAction')
            ->assertForbidden();

        $this->assertEquals('pending', $approval->fresh()->status);
    }

    public function test_check_approval_status_approval_milik_kasir_lain_tidak_verified()
    {
        // Server-side validation: walaupun state di-inject langsung (simulasi korup/tampered),
        // approval milik kasir lain tidak boleh memverifikasi.
        $shared = $this->makeDiscount('SHARED', null);
        $foreignApproval = DiscountApproval::create([
            'discount_id' => $shared->id,
            'status'      => 'approved',
            'kasir_id'    => $this->managerB->id, // milik user branch B
        ]);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $shared->kode_diskon);

        $instance = $component->instance();
        $instance->isWaitingApproval = true;
        $instance->approvalRequestId = $foreignApproval->id;

        $component->call('checkApprovalStatus');

        $this->assertFalse($component->get('isDiscountVerified'));
        $this->assertNull($component->get('verifiedDiscountId'));
        $this->assertFalse($component->get('isWaitingApproval'));
        $this->assertNull($component->get('approvalRequestId'));
    }

    public function test_check_approval_status_approval_discount_berbeda_tidak_verified()
    {
        $shared  = $this->makeDiscount('SHARED', null);
        $otherDisc = $this->makeDiscount('OTHER', $this->branchA->id);
        $mismatchApproval = DiscountApproval::create([
            'discount_id' => $otherDisc->id,
            'status'      => 'approved',
            'kasir_id'    => $this->kasirA->id, // kasir sendiri tapi discount berbeda
        ]);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $shared->kode_diskon);

        $instance = $component->instance();
        $instance->isWaitingApproval = true;
        $instance->approvalRequestId = $mismatchApproval->id;

        $component->call('checkApprovalStatus');

        $this->assertFalse($component->get('isDiscountVerified'));
        $this->assertNull($component->get('verifiedDiscountId'));
        $this->assertFalse($component->get('isWaitingApproval'));
        $this->assertNull($component->get('approvalRequestId'));
    }

    public function test_check_approval_status_valid_approval_membuat_verified()
    {
        Http::fake();

        $shared = $this->makeDiscount('SHARED', null);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $shared->kode_diskon);

        // 1. Request beneran lewat requestAdminApproval() (server membuat approval baru)
        $component->call('requestAdminApproval');

        // 2. Ambil approval BARU yang dibuat oleh server
        $approval = DiscountApproval::latest('id')->firstOrFail();
        $this->assertEquals('pending', $approval->status);

        // 3. Simulasi approver: update row menjadi approved
        $approval->update(['status' => 'approved']);

        // 4. Poll status
        $component->call('checkApprovalStatus');

        // 5. Verified terikat ke discount yang disetujui
        $this->assertTrue($component->get('isDiscountVerified'));
        $this->assertEquals($shared->id, $component->get('verifiedDiscountId'));
        $this->assertFalse($component->get('isWaitingApproval'));
        $this->assertNull($component->get('approvalRequestId'));
    }

    public function test_non_superadmin_tanpa_branch_tidak_bisa_buat_discount()
    {
        $managerNoBranch = User::factory()->create();
        $managerNoBranch->assignRole('manager');

        Livewire::actingAs($managerNoBranch)
            ->test(CreateDiscount::class)
            ->set('nama_diskon', 'Shared Hack')
            ->set('jenis_diskon', 'nominal')
            ->set('nilai_diskon', 500)
            ->set('is_active', 1)
            ->set('member_only', false)
            ->set('scope', 'global')
            ->set('branch_id', null)
            ->call('simpan')
            ->assertForbidden();

        $this->assertDatabaseMissing('discounts', ['nama_diskon' => 'Shared Hack']);
        $this->assertEquals(0, Discount::count());
    }

    public function test_non_superadmin_tanpa_branch_approval_list_kosong_dan_action_403()
    {
        $managerNoBranch = User::factory()->create();
        $managerNoBranch->assignRole('manager');

        $shared = $this->makeDiscount('SHARED', null);
        $approval = DiscountApproval::create([
            'discount_id' => $shared->id,
            'status'      => 'pending',
            'kasir_id'    => $this->kasirA->id,
        ]);

        // List kosong (fail closed)
        $component = Livewire::actingAs($managerNoBranch)->test(ApprovalList::class);
        $this->assertEmpty($component->viewData('approvals')->getCollection());

        // Direct action => 403
        Livewire::actingAs($managerNoBranch)
            ->test(ApprovalList::class)
            ->set('selectedApprovalId', $approval->id)
            ->set('actionType', 'approve')
            ->set('keterangan', 'ok')
            ->call('submitAction')
            ->assertForbidden();

        $this->assertEquals('pending', $approval->fresh()->status);
    }

    // ============================================================
    // 30-34. Private Discount Server-Authoritative Authorization
    // & Approval Replay
    // ============================================================

    public function test_save_order_private_discount_tanpa_verifikasi_ditolak()
    {
        $this->setupPosInfra();
        $priv = $this->makeDiscount('PRIV-A', $this->branchA->id, 'global', 'private');

        $pesananBefore = Pesanan::count();
        $itemBefore    = PesananItem::count();

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Tester')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('discountId', $priv->id);

        $component->call('saveOrder')->assertDispatched('showToast');

        $this->assertEquals($pesananBefore, Pesanan::count());
        $this->assertEquals($itemBefore, PesananItem::count());
        $this->assertEquals(0, $priv->fresh()->digunakan);
    }

    public function test_update_order_private_discount_tanpa_verifikasi_ditolak()
    {
        $this->setupPosInfra();
        $priv = $this->makeDiscount('PRIV-B', $this->branchA->id, 'global', 'private');

        $order = $this->saveOrderFor($this->kasirA, null);
        $beforeTotal = $order->fresh()->total;

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('orderId', $order->id)
            ->set('nama_costumer', 'Hacked')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('discount_id', $priv->id);

        $component->call('updateOrder')->assertDispatched('showToast');

        $fresh = $order->fresh();
        $this->assertEquals($beforeTotal, $fresh->total);
        $this->assertEquals('Tester', $fresh->nama);
        $this->assertNull($fresh->discount_id);
        $this->assertEquals(0, $priv->fresh()->digunakan);
    }

    public function test_verify_private_a_lalu_tamper_discount_id_b_ditolak_dan_binding_ke_a()
    {
        $this->setupPosInfra();
        $privA = $this->makeDiscount('PRIV-A', $this->branchA->id, 'global', 'private');
        $privB = $this->makeDiscount('PRIV-B', $this->branchA->id, 'global', 'private');

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $privA->kode_diskon)
            ->set('adminPassword', 'password');

        // Verifikasi lewat password superadmin (factory default 'password')
        $component->call('verifyDiscount');

        // D. Binding verifikasi: verifiedDiscountId harus A
        $this->assertTrue($component->get('isDiscountVerified'));
        $this->assertEquals($privA->id, $component->get('verifiedDiscountId'));

        // C. Tamper dalam SATU request: set discountId = private B + saveOrder
        // TANPA mengubah `discount`. (set('discount', ...) memicu updatedDiscount()
        // dan me-reset verification — test tidak boleh PASS hanya karena
        // verification sudah false; juga tidak boleh di-render ulang karena
        // render() merekomputasi discountId dari kode `discount`.)
        $component->update(
            calls: [
                ['method' => 'saveOrder', 'params' => [], 'path' => ''],
            ],
            updates: [
                'discountId'         => $privB->id,
                'nama_costumer'      => 'Tester',
                'metode_pembayaran'  => $this->paymentMethod->id,
                'sales_channel_id'   => $this->salesChannel->id,
                'pesanan'            => $this->cartPayload(),
            ],
        );

        // Discount B tidak boleh applied, tidak ada partial order
        $this->assertEquals(0, Pesanan::count());
        $this->assertEquals(0, $privB->fresh()->digunakan);
        $this->assertEquals(0, $privA->fresh()->digunakan);

        // Verification tetap terikat ke A (gagal = TIDAK reset authorization)
        $this->assertTrue($component->get('isDiscountVerified'));
        $this->assertEquals($privA->id, $component->get('verifiedDiscountId'));
    }

    public function test_approval_lama_tidak_bisa_dipakai_ulang_dari_client()
    {
        $priv = $this->makeDiscount('PRIV-OLD', $this->branchA->id, 'global', 'private');

        // Approval lama (approved) dari sesi sebelumnya
        $oldApproval = DiscountApproval::create([
            'discount_id' => $priv->id,
            'status'      => 'approved',
            'kasir_id'    => $this->kasirA->id,
        ]);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $priv->kode_diskon);

        // Client mencoba set approvalRequestId lama → properti Locked harus menolak/ignore
        try {
            $component->set('approvalRequestId', $oldApproval->id);
            $this->assertNotEquals($oldApproval->id, $component->get('approvalRequestId'));
        } catch (\Throwable $e) {
            // Expected: CannotUpdateLockedPropertyException
            $this->assertTrue(true);
        }

        // State verifikasi tetap tidak boleh berubah
        $this->assertFalse($component->get('isDiscountVerified'));
        $this->assertNull($component->get('verifiedDiscountId'));
        $this->assertNull($component->get('approvalRequestId'));

        // Approval lama tetap utuh (tidak dipakai/diubah oleh komponen)
        $this->assertEquals('approved', $oldApproval->fresh()->status);
    }

    // ============================================================
    // 35-47. Blocker Final: single-use private authorization,
    // shared usage lintas branch, legacy foreign reference,
    // password admin lintas branch
    // ============================================================

    public function test_verify_private_lalu_save_sukses_dan_state_verifikasi_reset()
    {
        $this->setupPosInfra();
        $priv = $this->makeDiscount('PRIV-A', $this->branchA->id, 'global', 'private');

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $priv->kode_diskon)
            ->set('adminPassword', 'password');

        $component->call('verifyDiscount');
        $this->assertTrue($component->get('isDiscountVerified'));
        $this->assertEquals($priv->id, $component->get('verifiedDiscountId'));

        $component
            ->set('discountId', $priv->id)
            ->set('nama_costumer', 'Tester')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload());

        $component->call('saveOrder')->assertDispatched('showToast');

        $order = Pesanan::latest('id')->firstOrFail();
        $this->assertEquals($priv->id, $order->discount_id);
        $this->assertEquals(5000, (int) $order->discount_value);
        $this->assertEquals(1, $priv->fresh()->digunakan);

        // Authorization single-use: reset setelah transaksi BERHASIL
        $this->assertFalse($component->get('isDiscountVerified'));
        $this->assertNull($component->get('verifiedDiscountId'));
        $this->assertFalse($component->get('isWaitingApproval'));
        $this->assertNull($component->get('approvalRequestId'));
    }

    public function test_private_single_use_order_kedua_tanpa_verify_ulang_ditolak()
    {
        $this->setupPosInfra();
        $priv = $this->makeDiscount('PRIV-A', $this->branchA->id, 'global', 'private');

        // Order 1: verify lalu save sukses
        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $priv->kode_diskon)
            ->set('adminPassword', 'password');
        $component->call('verifyDiscount');
        $component
            ->set('discountId', $priv->id)
            ->set('nama_costumer', 'Tester')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload());
        $component->call('saveOrder');

        $this->assertEquals(1, Pesanan::count());
        $this->assertEquals(1, $priv->fresh()->digunakan);

        // Order 2: komponen baru, TANPA verify ulang → ditolak.
        // Kirim discountId private A + saveOrder dalam SATU request (tamper).
        $pesananBefore = Pesanan::count();
        $itemBefore    = PesananItem::count();

        $component2 = Livewire::actingAs($this->kasirA)->test(CreateOrder::class);

        $component2->update(
            calls: [
                ['method' => 'saveOrder', 'params' => [], 'path' => ''],
            ],
            updates: [
                'discountId'         => $priv->id,
                'nama_costumer'      => 'Tester 2',
                'metode_pembayaran'  => $this->paymentMethod->id,
                'sales_channel_id'   => $this->salesChannel->id,
                'pesanan'            => $this->cartPayload(),
            ],
        );

        $this->assertEquals($pesananBefore, Pesanan::count());
        $this->assertEquals($itemBefore, PesananItem::count());
        $this->assertEquals(1, $priv->fresh()->digunakan);
    }

    public function test_edit_existing_private_tidak_membuat_reusable_verification_state()
    {
        $this->setupPosInfra();
        $priv = $this->makeDiscount('PRIV-A', $this->branchA->id, 'global', 'private');
        $order = $this->createPesananForBranch($priv, $this->branchA->id);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class, ['orderId' => base64_encode($order->id)]);

        // Kode discount accessible boleh tampil...
        $this->assertEquals($priv->kode_diskon, $component->get('discount'));

        // ...tetapi TIDAK boleh ada verification state yang reusable
        $this->assertFalse($component->get('isDiscountVerified'));
        $this->assertNull($component->get('verifiedDiscountId'));
    }

    public function test_update_existing_private_a_dengan_a_boleh_tanpa_pin_ulang()
    {
        $this->setupPosInfra();
        $priv = $this->makeDiscount('PRIV-A', $this->branchA->id, 'global', 'private');
        $order = $this->createPesananForBranch($priv, $this->branchA->id);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('orderId', $order->id)
            ->set('nama_costumer', 'Updated')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('discount_id', $priv->id);

        $component->call('updateOrder')->assertDispatched('showToast');

        $fresh = $order->fresh();
        $this->assertEquals('Updated', $fresh->nama);
        $this->assertEquals($priv->id, $fresh->discount_id);
        $this->assertEquals(5000, (int) $fresh->discount_value);
        $this->assertEquals(1, $priv->fresh()->digunakan);
    }

    public function test_update_existing_private_a_ke_private_b_tanpa_verifikasi_b_ditolak()
    {
        $this->setupPosInfra();
        $privA = $this->makeDiscount('PRIV-A', $this->branchA->id, 'global', 'private');
        $privB = $this->makeDiscount('PRIV-B', $this->branchA->id, 'global', 'private');
        $order = $this->createPesananForBranch($privA, $this->branchA->id);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('orderId', $order->id)
            ->set('nama_costumer', 'Hacked')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('discount_id', $privB->id);

        $component->call('updateOrder')->assertDispatched('showToast');

        $fresh = $order->fresh();
        $this->assertEquals($privA->id, $fresh->discount_id);
        $this->assertEquals(0, $privB->fresh()->digunakan);
    }

    public function test_load_edit_private_lalu_save_order_baru_tidak_boleh_pakai_authorization()
    {
        $this->setupPosInfra();
        $priv = $this->makeDiscount('PRIV-A', $this->branchA->id, 'global', 'private');
        $order = $this->createPesananForBranch($priv, $this->branchA->id);

        $pesananBefore = Pesanan::count();
        $itemBefore    = PesananItem::count();

        // Load edit existing order (editOrder dipanggil lewat mount)
        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class, ['orderId' => base64_encode($order->id)]);

        // Coba saveOrder() sebagai NEW order dalam SATU request dengan
        // discountId persisted → exemption existing order tidak berlaku.
        $component->update(
            calls: [
                ['method' => 'saveOrder', 'params' => [], 'path' => ''],
            ],
            updates: [
                'discountId'         => $priv->id,
                'nama_costumer'      => 'New Order',
                'metode_pembayaran'  => $this->paymentMethod->id,
                'sales_channel_id'   => $this->salesChannel->id,
                'pesanan'            => $this->cartPayload(),
            ],
        );

        $this->assertEquals($pesananBefore, Pesanan::count());
        $this->assertEquals($itemBefore, PesananItem::count());
        $this->assertEquals(0, $priv->fresh()->digunakan);
    }

    public function test_shared_discount_limit_global_tidak_terlewati_lintas_branch()
    {
        $this->setupPosInfra();
        $shared = $this->makeDiscount('SHARED', null, 'global', 'general');
        $shared->update(['limit' => 1, 'digunakan' => null]);

        // Sudah ada order branch B aktif memakai shared discount (legacy, digunakan NULL)
        $this->createPesananForBranch($shared, $this->branchB->id);

        // Branch A mencoba memakai discount yang sama
        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discountId', $shared->id)
            ->set('nama_costumer', 'Tester')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload());

        $component->call('saveOrder')->assertDispatched('showToast');

        $orderA = Pesanan::where('branch_id', $this->branchA->id)->latest('id')->firstOrFail();
        $this->assertNull($orderA->discount_id);
        $this->assertEquals(0, (int) $orderA->discount_value);
        // Limit global tidak terlewati & counter tidak disentuh
        $this->assertNull($shared->fresh()->digunakan);
    }

    public function test_cancel_order_a_shared_digunakan_null_menjadi_1_karena_b_masih_aktif()
    {
        $this->setupPosInfra();
        $shared = $this->makeDiscount('SHARED', null, 'global', 'general');
        $shared->update(['digunakan' => null]);

        $orderA = $this->createPesananForBranch($shared, $this->branchA->id);
        $orderB = $this->createPesananForBranch($shared, $this->branchB->id);

        Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->call('batalkanPesanan', $orderA->id)
            ->assertDispatched('showToast');

        $this->assertEquals('dibatalkan', $orderA->fresh()->status);
        $this->assertEquals('diproses', $orderB->fresh()->status);
        // Order B masih aktif → digunakan harus 1, TIDAK boleh 0
        $this->assertEquals(1, $shared->fresh()->digunakan);
    }

    public function test_update_order_a_hapus_shared_discount_reconciliation_menjadi_1()
    {
        $this->setupPosInfra();
        $shared = $this->makeDiscount('SHARED', null, 'global', 'general');
        $shared->update(['digunakan' => null]);

        $orderA = $this->createPesananForBranch($shared, $this->branchA->id);
        $orderB = $this->createPesananForBranch($shared, $this->branchB->id);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('orderId', $orderA->id)
            ->set('nama_costumer', 'Fixed')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('discount', '')
            ->set('discount_id', null);

        $component->call('updateOrder')->assertDispatched('showToast');

        $fresh = $orderA->fresh();
        $this->assertNull($fresh->discount_id);
        $this->assertEquals(0, (int) $fresh->discount_value);
        // Reconciliation lintas branch: order B masih aktif
        $this->assertEquals(1, $shared->fresh()->digunakan);
    }

    public function test_edit_order_legacy_foreign_discount_tidak_diekspos_dan_db_tidak_berubah()
    {
        $discB = $this->makeDiscount('DISC-B', $this->branchB->id);
        $order = $this->createPesananForBranch($discB, $this->branchA->id);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class, ['orderId' => base64_encode($order->id)]);

        // Kode discount B TIDAK terekspos ke user branch A
        $this->assertNull($component->get('discount'));
        $this->assertNull($component->get('discountId'));
        $this->assertNull($component->get('discount_id'));
        $this->assertFalse($component->get('isDiscountVerified'));
        $this->assertNull($component->get('verifiedDiscountId'));

        // Historical DB tidak diubah hanya karena membuka halaman
        $this->assertEquals($discB->id, $order->fresh()->discount_id);
    }

    public function test_update_malformed_order_foreign_discount_diperbaiki_counter_tidak_berubah()
    {
        $this->setupPosInfra();
        $discB = $this->makeDiscount('DISC-B', $this->branchB->id);
        $discB->update(['digunakan' => 7]);
        $order = $this->createPesananForBranch($discB, $this->branchA->id);

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('orderId', $order->id)
            ->set('nama_costumer', 'Fixed')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $this->cartPayload())
            ->set('discount', '')
            ->set('discount_id', null);

        $component->call('updateOrder')->assertDispatched('showToast');

        $fresh = $order->fresh();
        $this->assertEquals('Fixed', $fresh->nama);
        $this->assertNull($fresh->discount_id);
        $this->assertEquals(0, (int) $fresh->discount_value);
        // Counter discount B BUKAN milik branch A → tidak boleh berubah
        $this->assertEquals(7, $discB->fresh()->digunakan);
    }

    public function test_cancel_malformed_order_foreign_discount_counter_tidak_berubah()
    {
        $discB = $this->makeDiscount('DISC-B', $this->branchB->id);
        $discB->update(['digunakan' => 7]);
        $order = $this->createPesananForBranch($discB, $this->branchA->id);

        Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->call('batalkanPesanan', $order->id)
            ->assertDispatched('showToast');

        $this->assertEquals('dibatalkan', $order->fresh()->status);
        // Counter discount B tidak disentuh walaupun data historical malformed
        $this->assertEquals(7, $discB->fresh()->digunakan);
    }

    public function test_password_admin_branch_b_tidak_bisa_verify_discount_kasir_branch_a()
    {
        $priv = $this->makeDiscount('PRIV-X', $this->branchA->id, 'global', 'private');

        // Role 'admin' TIDAK ada di RbacSeeder → buat hanya di fixture test
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);

        $adminA = User::factory()->create([
            'branch_id' => $this->branchA->id,
            'password'  => bcrypt('secretA'),
        ]);
        $adminA->assignRole($adminRole);

        $adminB = User::factory()->create([
            'branch_id' => $this->branchB->id,
            'password'  => bcrypt('secretB'),
        ]);
        $adminB->assignRole($adminRole);

        // Password admin B (cabang lain) → TIDAK boleh verify
        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $priv->kode_diskon)
            ->set('adminPassword', 'secretB');

        $component->call('verifyDiscount');

        $this->assertFalse($component->get('isDiscountVerified'));
        $this->assertNull($component->get('verifiedDiscountId'));

        // Password admin A (cabang sama) → boleh
        $component->set('adminPassword', 'secretA');
        $component->call('verifyDiscount');

        $this->assertTrue($component->get('isDiscountVerified'));
        $this->assertEquals($priv->id, $component->get('verifiedDiscountId'));
    }

    public function test_password_superadmin_tetap_bisa_verify_lintas_branch()
    {
        $priv = $this->makeDiscount('PRIV-S', $this->branchA->id, 'global', 'private');

        $component = Livewire::actingAs($this->kasirA)
            ->test(CreateOrder::class)
            ->set('discount', $priv->kode_diskon)
            ->set('adminPassword', 'password');

        $component->call('verifyDiscount');

        $this->assertTrue($component->get('isDiscountVerified'));
        $this->assertEquals($priv->id, $component->get('verifiedDiscountId'));
    }
}
