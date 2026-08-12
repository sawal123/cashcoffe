<?php

namespace Tests\Feature;

use App\Livewire\Order\CreateOrder;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuPrice;
use App\Models\PaymentMethod;
use App\Models\Pesanan;
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

class ServerAuthoritativePricingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Menu $menuA;
    protected Menu $menuB;
    protected VariantGroup $groupA;
    protected VariantOption $optionA;
    protected VariantGroup $groupB;
    protected VariantOption $optionB;
    protected PaymentMethod $paymentMethod;
    protected SalesChannel $salesChannel;
    protected PriceTier $priceTier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('kasir');

        $this->priceTier = PriceTier::first() ?? PriceTier::create(['nama_tier' => 'Regular', 'is_active' => true]);
        $this->salesChannel = SalesChannel::first() ?? SalesChannel::create(['nama_channel' => 'Dine In', 'is_active' => true]);
        $this->paymentMethod = PaymentMethod::first() ?? PaymentMethod::create(['nama_metode' => 'Cash', 'kode_metode' => 'tunai', 'is_active' => true]);

        $category = Category::create(['nama' => 'Minuman']);

        $this->menuA = Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Kopi A',
            'harga' => 20000,
            'h_pokok' => 10000,
            'is_active' => true,
        ]);

        $this->menuB = Menu::create([
            'categories_id' => $category->id,
            'nama_menu' => 'Kopi B',
            'harga' => 25000,
            'h_pokok' => 12000,
            'is_active' => true,
        ]);

        $this->groupA = VariantGroup::create(['nama_group' => 'Ukuran']);
        $this->optionA = VariantOption::create([
            'variant_group_id' => $this->groupA->id,
            'nama_opsi' => 'Large',
            'extra_price' => 5000,
        ]);
        $this->menuA->variantGroups()->attach($this->groupA->id);

        $this->groupB = VariantGroup::create(['nama_group' => 'Topping']);
        $this->optionB = VariantOption::create([
            'variant_group_id' => $this->groupB->id,
            'nama_opsi' => 'Boba',
            'extra_price' => 3000,
        ]);
        $this->menuB->variantGroups()->attach($this->groupB->id);
    }

    // 1. Manipulasi harga Livewire/client menjadi 1 -> database tetap menyimpan harga resmi server.
    public function test_1_client_manipulated_price_uses_server_price()
    {
        $cartKey = (string) $this->menuA->id;
        $pesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 1,
                'gambar' => '',
                'qty' => 1,
                'selected_options' => [],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Budi')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $pesananData)
            ->call('saveOrder');

        $this->assertDatabaseHas('pesanan_items', [
            'menus_id' => $this->menuA->id,
            'harga_satuan' => 20000,
            'subtotal' => 20000,
        ]);

        $this->assertDatabaseHas('pesanans', [
            'total' => 20000,
        ]);
    }

    // 2. Manipulasi subtotal/total client -> total server tetap benar.
    public function test_2_client_manipulated_total_recalculated_by_server()
    {
        $cartKey = (string) $this->menuA->id;
        $pesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 100,
                'gambar' => '',
                'qty' => 2,
                'selected_options' => [],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Budi')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $pesananData)
            ->call('saveOrder');

        $this->assertDatabaseHas('pesanans', [
            'total' => 40000,
        ]);
    }

    // 3. Qty 0 ditolak.
    public function test_3_qty_zero_rejected()
    {
        $cartKey = (string) $this->menuA->id;
        $pesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 20000,
                'gambar' => '',
                'qty' => 0,
                'selected_options' => [],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Budi')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $pesananData)
            ->call('saveOrder');

        $this->assertDatabaseCount('pesanans', 0);
    }

    // 4. Qty negatif ditolak.
    public function test_4_qty_negative_rejected()
    {
        $cartKey = (string) $this->menuA->id;
        $pesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 20000,
                'gambar' => '',
                'qty' => -5,
                'selected_options' => [],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Budi')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $pesananData)
            ->call('saveOrder');

        $this->assertDatabaseCount('pesanans', 0);
    }

    // 5. Qty decimal/non-integer ditolak.
    public function test_5_qty_decimal_or_non_integer_rejected()
    {
        $cartKey = (string) $this->menuA->id;
        $pesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 20000,
                'gambar' => '',
                'qty' => 1.5,
                'selected_options' => [],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Budi')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $pesananData)
            ->call('saveOrder');

        $this->assertDatabaseCount('pesanans', 0);
    }

    // 6. Variant milik menu lain ditolak.
    public function test_6_variant_of_another_menu_rejected()
    {
        $cartKey = (string) $this->menuA->id;
        $pesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 20000,
                'gambar' => '',
                'qty' => 1,
                'selected_options' => [$this->optionB->id],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Budi')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $pesananData)
            ->call('saveOrder');

        $this->assertDatabaseCount('pesanans', 0);
    }

    // 7. Variant ID tidak valid ditolak.
    public function test_7_invalid_variant_id_rejected()
    {
        $cartKey = (string) $this->menuA->id;
        $pesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 20000,
                'gambar' => '',
                'qty' => 1,
                'selected_options' => [99999],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Budi')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $pesananData)
            ->call('saveOrder');

        $this->assertDatabaseCount('pesanans', 0);
    }

    // 8. Harga variant dimanipulasi -> harga database yang digunakan.
    public function test_8_manipulated_variant_price_uses_database_price()
    {
        $cartKey = (string) $this->menuA->id;
        $pesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 10,
                'gambar' => '',
                'qty' => 1,
                'selected_options' => [$this->optionA->id],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Budi')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $pesananData)
            ->call('saveOrder');

        $this->assertDatabaseHas('pesanans', [
            'total' => 25000,
        ]);
    }

    // 9. Price tier tidak valid ditolak.
    public function test_9_invalid_price_tier_rejected()
    {
        PriceTier::query()->delete();

        $cartKey = (string) $this->menuA->id;
        $pesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 20000,
                'gambar' => '',
                'qty' => 1,
                'selected_options' => [],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Budi')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $pesananData)
            ->call('saveOrder');

        $this->assertDatabaseCount('pesanans', 0);
    }

    // 10. Create order normal tetap berhasil.
    public function test_10_normal_create_order_succeeds()
    {
        $cartKey = (string) $this->menuA->id;
        $pesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 25000,
                'gambar' => '',
                'qty' => 2,
                'selected_options' => [$this->optionA->id],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Budi Normal')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $pesananData)
            ->call('saveOrder');

        $this->assertDatabaseHas('pesanans', [
            'nama' => 'Budi Normal',
            'total' => 50000,
        ]);
    }

    // 11. Update order normal tetap berhasil.
    public function test_11_normal_update_order_succeeds()
    {
        $cartKey = (string) $this->menuA->id;
        $pesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 20000,
                'gambar' => '',
                'qty' => 1,
                'selected_options' => [],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Budi Initial')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $pesananData)
            ->call('saveOrder');

        $pesanan = Pesanan::first();
        $this->assertNotNull($pesanan);

        $updatedCartKey = (string) $this->menuB->id;
        $updatedPesananData = [
            $updatedCartKey => [
                'id' => $this->menuB->id,
                'nama_menu' => $this->menuB->nama_menu,
                'harga' => 25000,
                'gambar' => '',
                'qty' => 2,
                'selected_options' => [],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('orderId', base64_encode($pesanan->id))
            ->call('editOrder', $pesanan->id)
            ->set('nama_costumer', 'Budi Updated')
            ->set('pesanan', $updatedPesananData)
            ->call('updateOrder');

        $this->assertDatabaseHas('pesanans', [
            'id' => $pesanan->id,
            'nama' => 'Budi Updated',
            'total' => 50000,
        ]);
    }

    // 12. Update order dengan harga client yang dimanipulasi tetap menggunakan harga server.
    public function test_12_update_order_with_manipulated_client_price_uses_server_price()
    {
        $cartKey = (string) $this->menuA->id;
        $pesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 20000,
                'gambar' => '',
                'qty' => 1,
                'selected_options' => [],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Budi Order')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $pesananData)
            ->call('saveOrder');

        $pesanan = Pesanan::first();

        $updatedPesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 1,
                'gambar' => '',
                'qty' => 2,
                'selected_options' => [],
            ],
        ];

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('orderId', base64_encode($pesanan->id))
            ->call('editOrder', $pesanan->id)
            ->set('pesanan', $updatedPesananData)
            ->call('updateOrder');

        $this->assertDatabaseHas('pesanans', [
            'id' => $pesanan->id,
            'total' => 40000,
        ]);
    }

    // 13. Perubahan harga database sebelum submit tercermin pada order baru/update.
    public function test_13_database_price_change_before_submit_is_reflected()
    {
        $cartKey = (string) $this->menuA->id;
        $pesananData = [
            $cartKey => [
                'id' => $this->menuA->id,
                'nama_menu' => $this->menuA->nama_menu,
                'harga' => 20000,
                'gambar' => '',
                'qty' => 1,
                'selected_options' => [],
            ],
        ];

        $this->menuA->update(['harga' => 35000]);

        Livewire::actingAs($this->user)
            ->test(CreateOrder::class)
            ->set('nama_costumer', 'Budi Dynamic')
            ->set('metode_pembayaran', $this->paymentMethod->id)
            ->set('sales_channel_id', $this->salesChannel->id)
            ->set('pesanan', $pesananData)
            ->call('saveOrder');

        $this->assertDatabaseHas('pesanans', [
            'total' => 35000,
        ]);
    }
}
