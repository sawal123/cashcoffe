<?php

namespace Tests\Feature;

use App\Livewire\Order\CreateOrder;
use App\Models\Category;
use App\Models\Menu;
use App\Models\PaymentMethod;
use App\Models\PriceTier;
use App\Models\SalesChannel;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartInputLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Menu $plainMenu;
    protected Menu $variantMenu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('kasir');

        PriceTier::first() ?? PriceTier::create(['nama_tier' => 'Regular', 'is_active' => true]);
        $salesChannel = SalesChannel::first() ?? SalesChannel::create(['nama_channel' => 'Dine In', 'is_active' => true]);
        PaymentMethod::first() ?? PaymentMethod::create(['nama_metode' => 'Cash', 'kode_metode' => 'tunai', 'is_active' => true]);

        $category = Category::create(['nama' => 'Minuman']);

        // Menu tanpa varian -> addPesanan langsung masuk keranjang.
        $this->plainMenu = Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Kopi Polos',
            'harga' => 20000,
            'h_pokok' => 10000,
            'is_active' => true,
        ]);

        // Menu dengan varian -> addPesanan membuka modal varian, tidak masuk keranjang langsung.
        $this->variantMenu = Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Kopi Varian',
            'harga' => 25000,
            'h_pokok' => 12000,
            'is_active' => true,
        ]);
        $group = \App\Models\VariantGroup::create(['nama_group' => 'Ukuran', 'selection_type' => 'single', 'is_required' => false]);
        \App\Models\VariantOption::create([
            'variant_group_id' => $group->id,
            'nama_opsi' => 'Large',
            'extra_price' => 5000,
        ]);
        $this->variantMenu->variantGroups()->attach($group->id);
    }

    // 1. Klik menu tanpa varian -> $pesanan bertambah, total berubah.
    public function test_add_pesanan_without_variant_adds_item_to_cart()
    {
        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('sales_channel_id', SalesChannel::first()->id)
            ->assertSet('pesanan', [])
            ->call('addPesanan', $this->plainMenu->id)
            ->assertSet('pesanan.' . $this->plainMenu->id . '.qty', 1)
            ->assertSet('pesanan.' . $this->plainMenu->id . '.harga', 20000);
    }

    // 2. Klik lagi -> qty bertambah.
    public function test_add_pesanan_again_increments_qty()
    {
        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('sales_channel_id', SalesChannel::first()->id)
            ->call('addPesanan', $this->plainMenu->id)
            ->call('addPesanan', $this->plainMenu->id)
            ->assertSet('pesanan.' . $this->plainMenu->id . '.qty', 2);
    }

    // 3. Menu dengan varian -> modal varian terbuka, tidak masuk $pesanan.
    public function test_add_pesanan_with_variant_opens_modal()
    {
        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('sales_channel_id', SalesChannel::first()->id)
            ->assertSet('pesanan', [])
            ->call('addPesanan', $this->variantMenu->id)
            ->assertSet('showVariantModal', true)
            ->assertSet('pesanan', []);
    }
}