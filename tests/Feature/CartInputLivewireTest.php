<?php

namespace Tests\Feature;

use App\Livewire\Order\CreateOrder;
use App\Livewire\Variant\TableVariantGroup;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Menu;
use App\Models\PaymentMethod;
use App\Models\PriceTier;
use App\Models\SalesChannel;
use App\Models\User;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use App\Models\VariantPrice;
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
    protected PriceTier $priceTier;
    protected SalesChannel $dineInChannel;
    protected SalesChannel $grabFoodChannel;
    protected VariantOption $variantOption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->priceTier = PriceTier::create(['nama_tier' => 'Regular', 'is_active' => true]);
        $this->dineInChannel = SalesChannel::create(['nama_channel' => 'Dine In', 'is_active' => true]);
        $this->grabFoodChannel = SalesChannel::create(['nama_channel' => 'GrabFood', 'is_active' => true]);
        PaymentMethod::first() ?? PaymentMethod::create(['nama_metode' => 'Cash', 'kode_metode' => 'tunai', 'is_active' => true]);

        $branch = Branch::create([
            'nama_cabang' => 'Cabang Test',
            'is_active' => true,
            'price_tier_id' => $this->priceTier->id,
        ]);

        $this->user = User::factory()->create(['branch_id' => $branch->id]);
        $this->user->assignRole('kasir');

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
        $group = VariantGroup::create(['nama_group' => 'Ukuran', 'selection_type' => 'single', 'is_required' => false]);
        $this->variantOption = VariantOption::create([
            'variant_group_id' => $group->id,
            'nama_opsi' => 'Large',
            'extra_price' => 0,
        ]);
        VariantPrice::create([
            'variant_option_id' => $this->variantOption->id,
            'price_tier_id' => $this->priceTier->id,
            'sales_channel_id' => $this->dineInChannel->id,
            'extra_price' => 3000,
        ]);
        VariantPrice::create([
            'variant_option_id' => $this->variantOption->id,
            'price_tier_id' => $this->priceTier->id,
            'sales_channel_id' => $this->grabFoodChannel->id,
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

    public function test_add_pesanan_variant_option_prices_follow_selected_sales_channel()
    {
        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('sales_channel_id', $this->dineInChannel->id)
            ->call('addPesanan', $this->variantMenu->id)
            ->assertSet('selectedMenuForVariant.option_prices.' . $this->variantOption->id, 3000)
            ->assertSee('+Rp 3.000');

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('sales_channel_id', $this->grabFoodChannel->id)
            ->call('addPesanan', $this->variantMenu->id)
            ->assertSet('selectedMenuForVariant.option_prices.' . $this->variantOption->id, 5000)
            ->assertSee('+Rp 5.000');
    }

    public function test_variant_group_list_shows_variant_price_range()
    {
        Livewire::actingAs($this->user)
            ->test(TableVariantGroup::class)
            ->assertSee('Rp 3.000 - Rp 5.000');
    }
}
