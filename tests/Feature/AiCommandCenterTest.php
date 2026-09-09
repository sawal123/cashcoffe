<?php

namespace Tests\Feature;

use App\Livewire\Admin\AiCommandCenter;
use App\Models\AiChatHistory;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Ingredients;
use App\Models\Menu;
use App\Models\MenuPrice;
use App\Models\PriceTier;
use App\Models\SalesChannel;
use App\Models\SatuanBahan;
use App\Models\Discount;
use App\Models\Member;
use App\Models\PaymentMethod;
use App\Models\Pesanan;
use App\Models\PesananItem;
use App\Models\RiwayatStock;
use App\Models\User;
use App\Models\VariantOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AiCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    protected $superadmin;

    protected $kasir;

    protected $category;

    protected $menu;

    protected $tier;

    protected $channel;

    protected $branchMedan;

    protected $satuanKg;

    protected $ingredient;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize Roles
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);

        // Create Users
        $this->superadmin = User::factory()->create(['name' => 'Super Admin']);
        $this->superadmin->assignRole('superadmin');

        $this->kasir = User::factory()->create(['name' => 'Budi']);
        $this->kasir->assignRole('kasir');

        // Seed Master Data
        $this->category = Category::create([
            'nama' => 'Kopi',
            'is_active' => true,
        ]);

        $this->menu = Menu::create([
            'nama_menu' => 'Kopi Susu',
            'categories_id' => $this->category->id,
            'h_pokok' => 10000,
            'harga' => 20000,
            'h_promo' => 0,
            'is_active' => true,
        ]);

        $this->tier = PriceTier::firstOrCreate([
            'nama_tier' => 'Mall',
        ], [
            'is_active' => true,
        ]);

        $this->channel = SalesChannel::firstOrCreate([
            'nama_channel' => 'GrabFood',
        ], [
            'is_active' => true,
        ]);

        $this->branchMedan = Branch::create([
            'nama_cabang' => 'Medan',
            'kode_cabang' => 'MDN',
            'is_active' => true,
        ]);

        $this->satuanKg = SatuanBahan::create([
            'nama_satuan' => 'Kg',
        ]);

        $this->ingredient = Ingredients::create([
            'nama_bahan' => 'Biji Kopi',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 10,
            'hpp' => 5000,
            'branch_id' => $this->branchMedan->id,
        ]);
    }

    public function test_non_superadmin_cannot_access_ai_command_center()
    {
        $this->actingAs($this->kasir)
            ->get(route('ai-command-center.index'))
            ->assertStatus(403);
    }

    public function test_superadmin_can_access_ai_command_center()
    {
        $this->actingAs($this->superadmin)
            ->get(route('ai-command-center.index'))
            ->assertStatus(200)
            ->assertSeeLivewire(AiCommandCenter::class);
    }

    public function test_chat_history_is_restored_after_component_is_reloaded()
    {
        AiChatHistory::create([
            'user_id' => $this->superadmin->id,
            'title' => 'Cek penjualan',
            'messages' => [
                [
                    'sender' => 'user',
                    'text' => 'Berapa penjualan Sanger?',
                    'time' => '10:00',
                ],
                [
                    'sender' => 'ai',
                    'text' => 'Sanger terjual 10 item.',
                    'time' => '10:01',
                ],
            ],
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->assertSee('Berapa penjualan Sanger?')
            ->assertSee('Sanger terjual 10 item.');
    }

    public function test_user_can_create_new_chat_and_continue_previous_chat()
    {
        $previousChat = AiChatHistory::create([
            'user_id' => $this->superadmin->id,
            'title' => 'Harga Sanger',
            'messages' => [
                [
                    'sender' => 'user',
                    'text' => 'Ubah harga Sanger',
                    'time' => '10:00',
                ],
                [
                    'sender' => 'ai',
                    'text' => 'Saya perlu tier dan channel.',
                    'time' => '10:01',
                ],
            ],
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->call('startNewChat')
            ->assertSee('Halo! Saya POS AI Assistant')
            ->call('loadChat', $previousChat->id)
            ->assertSee('Ubah harga Sanger')
            ->assertSee('Saya perlu tier dan channel.');

        $this->assertDatabaseCount('ai_chat_histories', 2);
    }

    public function test_superadmin_can_execute_price_update_command_successfully()
    {
        config(['services.openai.key' => 'mocked-key']);
        config(['services.openai.model' => 'gpt-4o-mini']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_action' => true,
                                'target_module' => 'PRICING',
                                'action_type' => 'UPDATE',
                                'ai_response' => 'Sip! Harga Kopi Susu berhasil diubah menjadi Rp25.000.',
                                'payload' => [
                                    'menu_name' => 'Kopi Susu',
                                    'variant_name' => '',
                                    'price_tier' => 'Mall',
                                    'sales_channel' => 'GrabFood',
                                    'price_value' => 25000,
                                    'employee_name' => '',
                                    'shift_name' => '',
                                    'item_name' => '',
                                    'branch_name' => '',
                                    'qty' => 0,
                                    'unit_name' => '',
                                    'fine_amount' => 0,
                                    'date' => '',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'Ubah harga Kopi Susu tier Mall channel GrabFood jadi 25000')
            ->call('executeCommand')
            ->assertHasNoErrors()
            ->assertSee('Status: Berhasil')
            ->assertSee('Harga menu Kopi Susu berhasil diperbarui')
            ->assertSee('Rp25.000')
            ->assertDispatched('showToast');

        $this->assertDatabaseHas('menu_prices', [
            'menu_id' => $this->menu->id,
            'price_tier_id' => $this->tier->id,
            'sales_channel_id' => $this->channel->id,
            'harga' => 25000,
        ]);
    }

    public function test_price_update_corrects_dine_in_when_ai_puts_it_in_tier_field()
    {
        config(['services.openai.key' => 'mocked-key']);

        $reguler = PriceTier::firstOrCreate(['nama_tier' => 'Reguler'], ['is_active' => true]);
        $dineIn = SalesChannel::firstOrCreate(['nama_channel' => 'Dine In'], ['is_active' => true]);

        MenuPrice::create([
            'menu_id' => $this->menu->id,
            'price_tier_id' => $reguler->id,
            'sales_channel_id' => $dineIn->id,
            'harga' => 20000,
            'h_promo' => 0,
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => true,
                            'is_in_scope' => true,
                            'target_module' => 'PRICING',
                            'action_type' => 'UPDATE',
                            'redirect_url' => '',
                            'ai_response' => 'Harga berhasil diubah.',
                            'payload' => [
                                'menu_name' => 'Kopi Susu',
                                'variant_name' => '',
                                'price_tier' => 'Dine In',
                                'sales_channel' => '',
                                'price_value' => 30000,
                                'employee_name' => '',
                                'shift_name' => '',
                                'item_name' => '',
                                'branch_name' => '',
                                'qty' => 0,
                                'unit_name' => '',
                                'fine_amount' => 0,
                                'date' => '',
                                'report_type' => 'none',
                                'date_from' => '',
                                'date_to' => '',
                                'limit' => 5,
                            ],
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'Tolong rubah harga menu Kopi Susu menjadi 30.000 di tier Dine In')
            ->call('executeCommand')
            ->assertSee('Status: Berhasil')
            ->assertSee('Tier: Reguler, Channel: Dine In')
            ->assertSee('Dine In')
            ->assertDispatched('showToast');

        $this->assertDatabaseHas('menu_prices', [
            'menu_id' => $this->menu->id,
            'price_tier_id' => $reguler->id,
            'sales_channel_id' => $dineIn->id,
            'harga' => 30000,
        ]);
    }

    public function test_price_update_reuses_legacy_null_dine_in_row()
    {
        config(['services.openai.key' => 'mocked-key']);

        $reguler = PriceTier::firstOrCreate(['nama_tier' => 'Reguler'], ['is_active' => true]);
        $dineIn = SalesChannel::firstOrCreate(['nama_channel' => 'Dine In'], ['is_active' => true]);

        $legacyPrice = MenuPrice::create([
            'menu_id' => $this->menu->id,
            'price_tier_id' => $reguler->id,
            'sales_channel_id' => null,
            'harga' => 20000,
            'h_promo' => 0,
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => true,
                            'is_in_scope' => true,
                            'target_module' => 'PRICING',
                            'action_type' => 'UPDATE',
                            'redirect_url' => '',
                            'ai_response' => 'Harga berhasil diubah.',
                            'payload' => [
                                'menu_name' => 'Kopi Susu',
                                'variant_name' => '',
                                'price_tier' => 'Reguler',
                                'sales_channel' => 'Dine In',
                                'price_value' => 30000,
                                'employee_name' => '',
                                'shift_name' => '',
                                'item_name' => '',
                                'branch_name' => '',
                                'qty' => 0,
                                'unit_name' => '',
                                'fine_amount' => 0,
                                'date' => '',
                                'report_type' => 'none',
                                'date_from' => '',
                                'date_to' => '',
                                'limit' => 5,
                            ],
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'Tolong rubah harga menu Kopi Susu menjadi 30.000 di tier Reguler channel Dine In')
            ->call('executeCommand')
            ->assertSee('Status: Berhasil');

        $this->assertDatabaseHas('menu_prices', [
            'id' => $legacyPrice->id,
            'sales_channel_id' => $dineIn->id,
            'harga' => 30000,
        ]);
    }

    public function test_ai_command_center_handles_menu_not_found_gracefully()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_action' => true,
                                'target_module' => 'PRICING',
                                'action_type' => 'UPDATE',
                                'ai_response' => 'Sedang memproses...',
                                'payload' => [
                                    'menu_name' => 'Es Kopi Item Luar Biasa',
                                    'variant_name' => '',
                                    'price_tier' => 'Mall',
                                    'sales_channel' => 'GrabFood',
                                    'price_value' => 25000,
                                    'employee_name' => '',
                                    'shift_name' => '',
                                    'item_name' => '',
                                    'branch_name' => '',
                                    'qty' => 0,
                                    'unit_name' => '',
                                    'fine_amount' => 0,
                                    'date' => '',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'Ubah harga Es Kopi Item Luar Biasa tier Mall channel GrabFood jadi 25000')
            ->call('executeCommand')
            ->assertSee('tidak ditemukan');
    }

    public function test_ai_command_center_handles_unknown_action_gracefully()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_action' => false,
                                'target_module' => 'GENERAL_CHAT',
                                'action_type' => 'none',
                                'ai_response' => 'Maaf, saya tidak mengerti maksud Anda.',
                                'payload' => [
                                    'menu_name' => '',
                                    'variant_name' => '',
                                    'price_tier' => '',
                                    'sales_channel' => '',
                                    'price_value' => 0,
                                    'employee_name' => '',
                                    'shift_name' => '',
                                    'item_name' => '',
                                    'branch_name' => '',
                                    'qty' => 0,
                                    'unit_name' => '',
                                    'fine_amount' => 0,
                                    'date' => '',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'Tampilkan peta dunia')
            ->call('executeCommand')
            ->assertSee('tidak mengerti maksud Anda');
    }

    public function test_superadmin_is_prompted_for_clarification_when_price_tier_is_omitted()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_action' => true,
                                'target_module' => 'PRICING',
                                'action_type' => 'UPDATE',
                                'ai_response' => 'Memproses...',
                                'payload' => [
                                    'menu_name' => 'Kopi Susu',
                                    'variant_name' => '',
                                    'price_tier' => '',
                                    'sales_channel' => 'GrabFood',
                                    'price_value' => 15000,
                                    'employee_name' => '',
                                    'shift_name' => '',
                                    'item_name' => '',
                                    'branch_name' => '',
                                    'qty' => 0,
                                    'unit_name' => '',
                                    'fine_amount' => 0,
                                    'date' => '',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'Ubah harga Kopi Susu jadi 15000')
            ->call('executeCommand')
            ->assertSee('Mau tier mana yang diubah?');
    }

    public function test_superadmin_can_update_inventory_stock_successfully()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_action' => true,
                                'target_module' => 'INVENTORY',
                                'action_type' => 'UPDATE',
                                'ai_response' => 'Stok Biji Kopi di Medan telah diubah menjadi 50.',
                                'payload' => [
                                    'menu_name' => '',
                                    'variant_name' => '',
                                    'price_tier' => '',
                                    'sales_channel' => '',
                                    'price_value' => 0,
                                    'employee_name' => '',
                                    'shift_name' => '',
                                    'item_name' => 'Biji Kopi',
                                    'branch_name' => 'Medan',
                                    'qty' => 50,
                                    'unit_name' => 'Kg',
                                    'fine_amount' => 0,
                                    'date' => '',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'Update stok biji kopi cabang Medan jadi 50')
            ->call('executeCommand')
            ->assertSee('Medan telah diubah menjadi 50');

        $this->assertDatabaseHas('ingredients', [
            'id' => $this->ingredient->id,
            'stok' => 50,
        ]);

        $this->assertDatabaseHas('riwayat_stocks', [
            'ingredient_id' => $this->ingredient->id,
            'qty' => 40, // 50 - 10 = 40
            'tipe' => 'in',
        ]);
    }

    public function test_superadmin_can_create_variant_option_successfully()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_action' => true,
                                'target_module' => 'VARIANT',
                                'action_type' => 'CREATE',
                                'ai_response' => 'Varian Large berhasil ditambahkan ke Kopi Susu.',
                                'payload' => [
                                    'menu_name' => 'Kopi Susu',
                                    'variant_name' => 'Large',
                                    'price_tier' => '',
                                    'sales_channel' => '',
                                    'price_value' => 0,
                                    'employee_name' => '',
                                    'shift_name' => '',
                                    'item_name' => '',
                                    'branch_name' => '',
                                    'qty' => 0,
                                    'unit_name' => '',
                                    'fine_amount' => 0,
                                    'date' => '',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'Tambahkan varian ukuran Large pada Kopi Susu')
            ->call('executeCommand')
            ->assertSee('Varian Large berhasil ditambahkan');

        $this->assertDatabaseHas('variant_options', [
            'nama_opsi' => 'Large',
        ]);
    }

    public function test_superadmin_can_create_fine_amount_successfully()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_action' => true,
                                'target_module' => 'HR_ATTENDANCE',
                                'action_type' => 'CREATE',
                                'ai_response' => 'Denda terlambat Budi sebesar Rp15.000 berhasil dicatat.',
                                'payload' => [
                                    'menu_name' => '',
                                    'variant_name' => '',
                                    'price_tier' => '',
                                    'sales_channel' => '',
                                    'price_value' => 0,
                                    'employee_name' => 'Budi',
                                    'shift_name' => '',
                                    'item_name' => '',
                                    'branch_name' => '',
                                    'qty' => 0,
                                    'unit_name' => '',
                                    'fine_amount' => 15000,
                                    'date' => '2026-05-21',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'Tambah denda telat Budi hari ini 15 ribu')
            ->call('executeCommand')
            ->assertSee('Denda terlambat Budi sebesar Rp15.000 berhasil dicatat');

        $this->assertDatabaseHas('absensis', [
            'user_id' => $this->kasir->id,
            'tanggal' => '2026-05-21',
            'status' => 'terlambat',
            'denda_missing_clockout' => 15000,
        ]);
    }

    public function test_superadmin_can_query_best_selling_menu_successfully()
    {
        config(['services.openai.key' => 'mocked-key']);

        // Create a dummy Pesanan and PesananItem
        $pesanan = \App\Models\Pesanan::create([
            'kode' => 'TX001',
            'sales_channel_id' => $this->channel->id,
            'branch_id' => $this->branchMedan->id,
            'status' => 'selesai',
            'total' => 20000,
        ]);

        \App\Models\PesananItem::create([
            'pesanans_id' => $pesanan->id,
            'menus_id' => $this->menu->id,
            'qty' => 5,
            'harga_satuan' => 20000,
            'subtotal' => 100000,
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_action' => false,
                                'target_module' => 'GENERAL_CHAT',
                                'action_type' => 'READ',
                                'ai_response' => 'Berikut infonya:',
                                'payload' => [
                                    'menu_name' => '',
                                    'variant_name' => '',
                                    'price_tier' => '',
                                    'sales_channel' => '',
                                    'price_value' => 0,
                                    'employee_name' => '',
                                    'shift_name' => '',
                                    'item_name' => '',
                                    'branch_name' => '',
                                    'qty' => 0,
                                    'unit_name' => '',
                                    'fine_amount' => 0,
                                    'date' => '',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'carikan saya menu paling laris')
            ->call('executeCommand')
            ->assertSee('menu paling laris')
            ->assertSee('Kopi Susu');
    }

    public function test_ai_can_answer_specific_menu_sales_from_completed_orders()
    {
        config(['services.openai.key' => 'mocked-key']);

        $sanger = Menu::create([
            'nama_menu' => 'Sanger',
            'categories_id' => $this->category->id,
            'h_pokok' => 10000,
            'harga' => 20000,
            'h_promo' => 0,
            'is_active' => true,
        ]);

        $completedOrder = \App\Models\Pesanan::create([
            'kode' => 'SANGER-SELESAI',
            'status' => 'selesai',
            'total' => 60000,
        ]);

        \App\Models\PesananItem::create([
            'pesanans_id' => $completedOrder->id,
            'menus_id' => $sanger->id,
            'qty' => 3,
            'harga_satuan' => 20000,
            'subtotal' => 60000,
        ]);

        $cancelledOrder = \App\Models\Pesanan::create([
            'kode' => 'SANGER-BATAL',
            'status' => 'dibatalkan',
            'total' => 100000,
        ]);

        \App\Models\PesananItem::create([
            'pesanans_id' => $cancelledOrder->id,
            'menus_id' => $sanger->id,
            'qty' => 5,
            'harga_satuan' => 20000,
            'subtotal' => 100000,
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'is_in_scope' => true,
                            'target_module' => 'SALES_REPORT',
                            'action_type' => 'READ',
                            'redirect_url' => '',
                            'ai_response' => 'Saya cek data penjualan.',
                            'payload' => [
                                'menu_name' => 'Sanger',
                                'variant_name' => '',
                                'price_tier' => '',
                                'sales_channel' => '',
                                'price_value' => 0,
                                'employee_name' => '',
                                'shift_name' => '',
                                'item_name' => '',
                                'branch_name' => '',
                                'qty' => 0,
                                'unit_name' => '',
                                'fine_amount' => 0,
                                'date' => '',
                                'report_type' => 'menu_sales',
                                'date_from' => '',
                                'date_to' => '',
                                'limit' => 5,
                            ],
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'Carikan berapa penjualan menu Sanger')
            ->call('executeCommand')
            ->assertSee('Penjualan menu Sanger')
            ->assertSee('Terjual: 3 item')
            ->assertSee('Jumlah transaksi: 1')
            ->assertSee('Omzet item: Rp60.000');

        $savedMessages = AiChatHistory::where('user_id', $this->superadmin->id)
            ->firstOrFail()
            ->messages;

        $this->assertTrue(
            collect($savedMessages)->contains(
                fn ($message) => str_contains($message['text'], 'Penjualan menu Sanger')
            )
        );
    }

    public function test_ai_refuses_questions_outside_cash_coffee_application_scope()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'is_in_scope' => false,
                            'target_module' => 'OUT_OF_SCOPE',
                            'action_type' => 'none',
                            'redirect_url' => '',
                            'ai_response' => 'Jawaban yang tidak boleh ditampilkan.',
                            'payload' => [
                                'menu_name' => '',
                                'variant_name' => '',
                                'price_tier' => '',
                                'sales_channel' => '',
                                'price_value' => 0,
                                'employee_name' => '',
                                'shift_name' => '',
                                'item_name' => '',
                                'branch_name' => '',
                                'qty' => 0,
                                'unit_name' => '',
                                'fine_amount' => 0,
                                'date' => '',
                                'report_type' => 'none',
                                'date_from' => '',
                                'date_to' => '',
                                'limit' => 5,
                            ],
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'Siapa presiden negara lain?')
            ->call('executeCommand')
            ->assertSee('hanya dapat membantu pertanyaan dan pekerjaan yang berkaitan dengan aplikasi Cash Coffee')
            ->assertDontSee('Jawaban yang tidak boleh ditampilkan');
    }

    public function test_kasir_role_denied_access_to_superadmin_actions()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_action' => true,
                                'target_module' => 'PRICING',
                                'action_type' => 'UPDATE',
                                'redirect_url' => '',
                                'ai_response' => 'Ubah harga menu...',
                                'payload' => [
                                    'menu_name' => 'Kopi Susu',
                                    'variant_name' => '',
                                    'price_tier' => 'Mall',
                                    'sales_channel' => 'GrabFood',
                                    'price_value' => 25000,
                                    'employee_name' => '',
                                    'shift_name' => '',
                                    'item_name' => '',
                                    'branch_name' => '',
                                    'qty' => 0,
                                    'unit_name' => '',
                                    'fine_amount' => 0,
                                    'date' => '',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->kasir)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'Ubah harga Kopi Susu tier Mall channel GrabFood jadi 25000')
            ->call('executeCommand')
            ->assertSee('Maaf, Anda tidak memiliki akses ke fitur tersebut.');
    }

    public function test_superadmin_can_trigger_redirect()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_action' => true,
                                'target_module' => 'GENERAL_CHAT',
                                'action_type' => 'REDIRECT',
                                'redirect_url' => '/member/15/edit',
                                'ai_response' => 'Membuka halaman edit member...',
                                'payload' => [
                                    'menu_name' => '',
                                    'variant_name' => '',
                                    'price_tier' => '',
                                    'sales_channel' => '',
                                    'price_value' => 0,
                                    'employee_name' => '',
                                    'shift_name' => '',
                                    'item_name' => '',
                                    'branch_name' => '',
                                    'qty' => 0,
                                    'unit_name' => '',
                                    'fine_amount' => 0,
                                    'date' => '',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $component = Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'buka halaman edit member budi')
            ->call('executeCommand');

        $component->assertRedirect('/member/15/edit');
    }

    public function test_kasir_cannot_redirect_to_superadmin_url()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_action' => true,
                                'target_module' => 'GENERAL_CHAT',
                                'action_type' => 'REDIRECT',
                                'redirect_url' => '/payroll/generasi',
                                'ai_response' => 'Membuka halaman payroll...',
                                'payload' => [
                                    'menu_name' => '',
                                    'variant_name' => '',
                                    'price_tier' => '',
                                    'sales_channel' => '',
                                    'price_value' => 0,
                                    'employee_name' => '',
                                    'shift_name' => '',
                                    'item_name' => '',
                                    'branch_name' => '',
                                    'qty' => 0,
                                    'unit_name' => '',
                                    'fine_amount' => 0,
                                    'date' => '',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->kasir)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'buka halaman payroll')
            ->call('executeCommand')
            ->assertSee('Maaf, Anda tidak memiliki akses ke fitur tersebut.');
    }

    public function test_superadmin_can_update_price_for_all_tiers_and_channels_successfully()
    {
        config(['services.openai.key' => 'mocked-key']);
        config(['services.openai.model' => 'gpt-4o-mini']);

        // Create an additional tier and channel to test multi-update
        $tier2 = PriceTier::create(['nama_tier' => 'Reguler', 'is_active' => true]);
        $channel2 = SalesChannel::create(['nama_channel' => 'Dine In', 'is_active' => true]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_action' => true,
                                'target_module' => 'PRICING',
                                'action_type' => 'UPDATE',
                                'ai_response' => 'Harga Kopi Susu berhasil diperbarui menjadi Rp5.000 untuk semua tier dan channel.',
                                'payload' => [
                                    'menu_name' => 'Kopi Susu',
                                    'variant_name' => '',
                                    'price_tier' => 'all',
                                    'sales_channel' => 'all',
                                    'price_value' => 5000,
                                    'employee_name' => '',
                                    'shift_name' => '',
                                    'item_name' => '',
                                    'branch_name' => '',
                                    'qty' => 0,
                                    'unit_name' => '',
                                    'fine_amount' => 0,
                                    'date' => '',
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'ubah harga kopi susu menjadi 5000 di semua tier dan channel')
            ->call('executeCommand')
            ->assertHasNoErrors();

        // Assert all 4 combinations (2 tiers x 2 channels) are updated/created with price 5000
        $this->assertDatabaseHas('menu_prices', [
            'menu_id' => $this->menu->id,
            'price_tier_id' => $this->tier->id,
            'sales_channel_id' => $this->channel->id,
            'harga' => 5000,
        ]);
        $this->assertDatabaseHas('menu_prices', [
            'menu_id' => $this->menu->id,
            'price_tier_id' => $tier2->id,
            'sales_channel_id' => $this->channel->id,
            'harga' => 5000,
        ]);
        $this->assertDatabaseHas('menu_prices', [
            'menu_id' => $this->menu->id,
            'price_tier_id' => $this->tier->id,
            'sales_channel_id' => $channel2->id,
            'harga' => 5000,
        ]);
        $this->assertDatabaseHas('menu_prices', [
            'menu_id' => $this->menu->id,
            'price_tier_id' => $tier2->id,
            'sales_channel_id' => $channel2->id,
            'harga' => 5000,
        ]);
    }

    public function test_superadmin_can_query_least_selling_menu_successfully()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'is_action' => false,
                                'target_module' => 'GENERAL_CHAT',
                                'action_type' => 'READ',
                                'ai_response' => 'Berikut infonya:',
                                'payload' => [
                                    'menu_name' => '',
                                    'variant_name' => '',
                                    'price_tier' => '',
                                    'sales_channel' => '',
                                    'price_value' => 0,
                                    'employee_name' => '',
                                    'shift_name' => '',
                                    'item_name' => '',
                                    'branch_name' => '',
                                    'qty' => 0,
                                    'unit_name' => '',
                                    'fine_amount' => 0,
                                    'date' => '',
                                    'report_type' => 'least_selling_menus',
                                    'date_from' => '',
                                    'date_to' => '',
                                    'limit' => 5,
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'menu mana yang paling sedikit dipesan?')
            ->call('executeCommand')
            ->assertSee('Menu paling sedikit dipesan')
            ->assertSee('Kopi Susu');
    }

    private function defaultPayload(array $overrides = []): array
    {
        return array_merge([
            'menu_name' => '',
            'variant_name' => '',
            'price_tier' => '',
            'sales_channel' => '',
            'price_value' => 0,
            'employee_name' => '',
            'shift_name' => '',
            'item_name' => '',
            'branch_name' => '',
            'qty' => 0,
            'unit_name' => '',
            'fine_amount' => 0,
            'date' => '',
            'report_type' => 'none',
            'date_from' => '',
            'date_to' => '',
            'limit' => 10,
            'invoice_number' => '',
            'member_name' => '',
            'member_phone' => '',
            'member_email' => '',
            'payment_method' => '',
            'stock_type' => '',
        ], $overrides);
    }

    public function test_superadmin_can_query_transaction_detail_by_invoice_successfully()
    {
        config(['services.openai.key' => 'mocked-key']);

        $memberUser = User::factory()->create(['name' => 'Sawal Member']);
        $member = Member::create([
            'user_id' => $memberUser->id,
            'phone' => '081234567899',
            'points' => 50,
            'total_pengeluaran' => 100000,
        ]);

        $pm = PaymentMethod::where('kode_metode', 'qris')->first()
            ?? PaymentMethod::create(['nama_metode' => 'QRIS', 'kode_metode' => 'qris', 'is_active' => true]);

        $discount = Discount::create([
            'nama_diskon' => 'Voucher 5K',
            'type' => 'fixed',
            'kode_diskon' => 'V5K',
            'jenis_diskon' => 'nominal',
            'nilai_diskon' => 5000,
            'scope' => 'global',
            'is_active' => true,
        ]);

        $order = Pesanan::create([
            'kode' => 'INV-TEST-001',
            'nama' => 'Sawal',
            'user_id' => $this->kasir->id,
            'member_id' => $member->id,
            'branch_id' => $this->branchMedan->id,
            'sales_channel_id' => $this->channel->id,
            'payment_method_id' => $pm->id,
            'discount_id' => $discount->id,
            'discount_value' => 5000,
            'total' => 25000,
            'status' => 'selesai',
        ]);

        $item = PesananItem::create([
            'pesanans_id' => $order->id,
            'menus_id' => $this->menu->id,
            'qty' => 2,
            'harga_satuan' => 10000,
            'subtotal' => 20000,
        ]);

        $variantGroup = \App\Models\VariantGroup::create(['nama_group' => 'Ukuran']);
        $variant = VariantOption::create([
            'variant_group_id' => $variantGroup->id,
            'nama_opsi' => 'Large',
        ]);
        $item->variants()->attach($variant->id);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'TRANSACTION',
                            'action_type' => 'READ',
                            'ai_response' => 'Berikut detailnya:',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'transaction_detail',
                                'invoice_number' => 'INV-TEST-001',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'cek invoice INV-TEST-001')
            ->call('executeCommand')
            ->assertSee('Detail Transaksi')
            ->assertSee('Invoice: INV-TEST-001')
            ->assertSee('Status: Selesai')
            ->assertSee('Kasir: Budi')
            ->assertSee('Cabang: Medan')
            ->assertSee('Pembayaran: QRIS')
            ->assertSee('Member: Sawal Member')
            ->assertSee('Kopi Susu (Large) × 2')
            ->assertSee('Total: Rp25.000')
            ->assertSee('Diskon: Rp5.000')
            ->assertSee('Total Akhir: Rp20.000')
            ->assertSee('/transaksi')
            ->assertSee('/print/struk/'.$order->id);
    }

    public function test_query_transaction_detail_not_found_returns_clear_message()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'TRANSACTION',
                            'action_type' => 'READ',
                            'ai_response' => 'Mencari...',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'transaction_detail',
                                'invoice_number' => 'INV-NOT-FOUND',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'detail pesanan INV-NOT-FOUND')
            ->call('executeCommand')
            ->assertSee("Transaksi dengan invoice 'INV-NOT-FOUND' tidak ditemukan.");
    }

    public function test_raw_invoice_input_is_deterministically_resolved_to_transaction_detail()
    {
        config(['services.openai.key' => 'mocked-key']);

        $order = Pesanan::create([
            'kode' => 'INV-EXACT-888',
            'nama' => 'Pelanggan Direct',
            'user_id' => $this->kasir->id,
            'sales_channel_id' => $this->channel->id,
            'total' => 15000,
            'status' => 'selesai',
        ]);

        // Mock OpenAI returning report_type 'none'
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'GENERAL_CHAT',
                            'action_type' => 'none',
                            'ai_response' => 'Halo ada yang bisa dibantu?',
                            'payload' => $this->defaultPayload(),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'INV-EXACT-888')
            ->call('executeCommand')
            ->assertSee('Detail Transaksi')
            ->assertSee('Invoice: INV-EXACT-888')
            ->assertSee('Pelanggan Direct');
    }

    public function test_superadmin_can_query_member_info_by_name()
    {
        config(['services.openai.key' => 'mocked-key']);

        $user = User::factory()->create([
            'name' => 'Sawaluddin Siregar',
            'email' => 'sawal@cashcoffee.test',
            'password' => bcrypt('supersecret123'),
            'remember_token' => 'secrettoken123',
        ]);

        $member = Member::create([
            'user_id' => $user->id,
            'phone' => '081299998888',
            'address' => 'Medan Kota',
            'points' => 320,
            'total_pengeluaran' => 1500000,
        ]);

        Pesanan::create([
            'kode' => 'INV-MBR-01',
            'nama' => 'Sawal',
            'user_id' => $this->kasir->id,
            'member_id' => $member->id,
            'sales_channel_id' => $this->channel->id,
            'total' => 100000,
            'status' => 'selesai',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'MEMBER',
                            'action_type' => 'READ',
                            'ai_response' => 'Mencari data member...',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'member_info',
                                'member_name' => 'Sawaluddin Siregar',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'informasi member Sawaluddin Siregar')
            ->call('executeCommand')
            ->assertSee('Informasi Member')
            ->assertSee('Nama: Sawaluddin Siregar')
            ->assertSee('Email: sawal@cashcoffee.test')
            ->assertSee('Telepon: 081299998888')
            ->assertSee('Alamat: Medan Kota')
            ->assertSee('Poin: 320')
            ->assertSee('Total Pengeluaran: Rp1.500.000')
            ->assertSee('Jumlah Transaksi: 1')
            ->assertSee('INV-MBR-01')
            ->assertSee('/member/'.$member->id.'/edit')
            ->assertDontSee('supersecret123')
            ->assertDontSee('secrettoken123');
    }

    public function test_superadmin_can_query_member_info_by_phone()
    {
        config(['services.openai.key' => 'mocked-key']);

        $user = User::factory()->create(['name' => 'Budi Member']);
        $member = Member::create([
            'user_id' => $user->id,
            'phone' => '087711223344',
            'points' => 75,
            'total_pengeluaran' => 250000,
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'MEMBER',
                            'action_type' => 'READ',
                            'ai_response' => 'Cek member...',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'member_info',
                                'member_phone' => '087711223344',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'member nomor 087711223344')
            ->call('executeCommand')
            ->assertSee('Budi Member')
            ->assertSee('087711223344')
            ->assertSee('Poin: 75');
    }

    public function test_member_not_found_handled_properly()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'MEMBER',
                            'action_type' => 'READ',
                            'ai_response' => 'Cek member...',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'member_info',
                                'member_name' => 'Fulan Anonymous',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'cari member Fulan Anonymous')
            ->call('executeCommand')
            ->assertSee("Member 'Fulan Anonymous' tidak ditemukan.");
    }

    public function test_superadmin_can_query_all_members_list_with_limit_and_total_count()
    {
        config(['services.openai.key' => 'mocked-key']);

        for ($i = 1; $i <= 12; $i++) {
            $u = User::factory()->create(['name' => "Member {$i}"]);
            Member::create([
                'user_id' => $u->id,
                'phone' => '0811000000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'points' => $i * 10,
                'total_pengeluaran' => $i * 50000,
            ]);
        }

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'MEMBER',
                            'action_type' => 'READ',
                            'ai_response' => 'Berikut daftar member:',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'member_info',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'lihat semua member')
            ->call('executeCommand')
            ->assertSee('Total member: 12')
            ->assertSee('/member');
    }

    public function test_superadmin_can_query_payment_methods_list()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'PAYMENT',
                            'action_type' => 'READ',
                            'ai_response' => 'Berikut metode pembayarannya:',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'payment_methods',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'metode pembayaran apa saja')
            ->call('executeCommand')
            ->assertSee('Metode Pembayaran')
            ->assertSee('Tunai')
            ->assertSee('Status: Aktif')
            ->assertSee('/payment-method');
    }

    public function test_superadmin_can_query_payment_method_statistics()
    {
        config(['services.openai.key' => 'mocked-key']);

        $qris = PaymentMethod::where('kode_metode', 'qris')->first()
            ?? PaymentMethod::create(['nama_metode' => 'QRIS', 'kode_metode' => 'qris', 'is_active' => true]);

        Pesanan::create([
            'kode' => 'ORD-PM-01',
            'nama' => 'Test QRIS',
            'user_id' => $this->kasir->id,
            'payment_method_id' => $qris->id,
            'sales_channel_id' => $this->channel->id,
            'total' => 100000,
            'discount_value' => 10000,
            'status' => 'selesai',
            'created_at' => now(),
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'PAYMENT',
                            'action_type' => 'READ',
                            'ai_response' => 'Berikut statistik pembayaran:',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'payment_methods',
                                'payment_method' => 'QRIS',
                                'date_from' => now()->toDateString(),
                                'date_to' => now()->toDateString(),
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'berapa transaksi QRIS hari ini')
            ->call('executeCommand')
            ->assertSee('Penggunaan Metode Pembayaran')
            ->assertSee('QRIS')
            ->assertSee('Transaksi: 1')
            ->assertSee('Omzet: Rp90.000');
    }

    public function test_inventory_stock_filter_habis_and_menipis()
    {
        config(['services.openai.key' => 'mocked-key']);

        Ingredients::create([
            'nama_bahan' => 'Gula Habis',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 0,
            'hpp' => 3000,
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'INVENTORY',
                            'action_type' => 'READ',
                            'ai_response' => 'Cek stok...',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'inventory_stock',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'stok apa yang habis')
            ->call('executeCommand')
            ->assertSee('Gula Habis')
            ->assertSee('/stock-dapur');
    }

    public function test_superadmin_can_query_stock_history_with_transaction_invoice_highlight()
    {
        config(['services.openai.key' => 'mocked-key']);

        $susu = Ingredients::create([
            'nama_bahan' => 'Susu Segar',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 8,
            'hpp' => 15000,
        ]);

        RiwayatStock::create([
            'ingredient_id' => $susu->id,
            'kode' => 'OUT-999',
            'qty' => 2,
            'qty_before' => 10,
            'qty_after' => 8,
            'tipe' => 'out',
            'keterangan' => 'Akumulasi resep: pesanan INV-ORD-999',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'INVENTORY',
                            'action_type' => 'READ',
                            'ai_response' => 'Berikut riwayat stoknya:',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'stock_history',
                                'item_name' => 'Susu Segar',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'kenapa stok susu berkurang?')
            ->call('executeCommand')
            ->assertSee('Riwayat Stock — Susu Segar')
            ->assertSee('Stok sekarang: 8 Kg')
            ->assertSee('OUT 2 Kg')
            ->assertSee('10 Kg → 8 Kg')
            ->assertSee('Akumulasi resep: pesanan INV-ORD-999')
            ->assertSee('Invoice terkait: INV-ORD-999')
            ->assertSee('/riwayat-stock');
    }

    public function test_stock_history_filter_in_and_out()
    {
        config(['services.openai.key' => 'mocked-key']);

        $teh = Ingredients::create([
            'nama_bahan' => 'Daun Teh',
            'satuan_id' => $this->satuanKg->id,
            'stok' => 15,
            'hpp' => 5000,
        ]);

        RiwayatStock::create([
            'ingredient_id' => $teh->id,
            'kode' => 'IN-111',
            'qty' => 5,
            'qty_before' => 10,
            'qty_after' => 15,
            'tipe' => 'in',
            'keterangan' => 'Penerimaan bahan baru',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'INVENTORY',
                            'action_type' => 'READ',
                            'ai_response' => 'Berikut stok masuk:',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'stock_history',
                                'item_name' => 'Daun Teh',
                                'stock_type' => 'in',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'stok apa saja yang masuk hari ini')
            ->call('executeCommand')
            ->assertSee('IN 5 Kg')
            ->assertSee('Penerimaan bahan baru');
    }

    public function test_adversarial_member_count_with_corrupted_member_name()
    {
        config(['services.openai.key' => 'mocked-key']);

        $user = User::factory()->create(['name' => 'Member Test']);
        Member::create([
            'user_id' => $user->id,
            'phone' => '081299990001',
            'points' => 50,
            'total_pengeluaran' => 100000,
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'MEMBER',
                            'action_type' => 'READ',
                            'ai_response' => 'Cek member...',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'member_info',
                                'member_name' => 'ada berapa',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'ada berapa member?')
            ->call('executeCommand')
            ->assertSee('Total member terdaftar:')
            ->assertDontSee("Member 'ada berapa' tidak ditemukan.");
    }

    public function test_adversarial_member_count_with_report_type_none()
    {
        config(['services.openai.key' => 'mocked-key']);

        $user = User::factory()->create(['name' => 'Member Count User']);
        Member::create([
            'user_id' => $user->id,
            'phone' => '081299990002',
            'points' => 10,
            'total_pengeluaran' => 50000,
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'GENERAL_CHAT',
                            'action_type' => 'none',
                            'ai_response' => 'Saya tidak tahu data member.',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'none',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'berapa member?')
            ->call('executeCommand')
            ->assertSee('Total member terdaftar:');
    }

    public function test_adversarial_stock_minus_with_ai_redirect()
    {
        config(['services.openai.key' => 'mocked-key']);

        $susu = Ingredients::create([
            'nama_bahan' => 'Susu Minus Test',
            'satuan_id' => $this->satuanKg->id,
            'stok' => -5,
            'hpp' => 4000,
            'branch_id' => $this->branchMedan->id,
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => true,
                            'target_module' => 'INVENTORY',
                            'action_type' => 'REDIRECT',
                            'redirect_url' => '/stock-dapur',
                            'report_type' => 'none',
                            'ai_response' => 'Silakan buka halaman Stock Dapur',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'none',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $comp = Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'stock apa saja yang minus')
            ->call('executeCommand');

        $comp->assertSee('Susu Minus Test')
            ->assertSee('-5')
            ->assertSee('minus');
    }

    public function test_adversarial_stock_minus_with_wrong_payload_item_name()
    {
        config(['services.openai.key' => 'mocked-key']);

        $susu = Ingredients::create([
            'nama_bahan' => 'Susu Minus Hal',
            'satuan_id' => $this->satuanKg->id,
            'stok' => -8,
            'hpp' => 4000,
            'branch_id' => $this->branchMedan->id,
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'INVENTORY',
                            'action_type' => 'READ',
                            'ai_response' => 'Berikut data stok:',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'inventory_stock',
                                'item_name' => 'apa saja',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'stok apa saja yang minus')
            ->call('executeCommand')
            ->assertSee('Susu Minus Hal')
            ->assertSee('-8');
    }

    public function test_adversarial_payment_methods_with_ai_redirect()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => true,
                            'target_module' => 'PAYMENT',
                            'action_type' => 'REDIRECT',
                            'redirect_url' => '/payment-method',
                            'ai_response' => 'Buka payment method',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'none',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'metode pembayaran apa saja')
            ->call('executeCommand')
            ->assertSee('Metode Pembayaran')
            ->assertSee('QRIS');
    }

    public function test_adversarial_transaction_detail_with_ai_redirect()
    {
        config(['services.openai.key' => 'mocked-key']);

        $order = Pesanan::create([
            'kode' => 'INV-ADV-999',
            'nama' => 'Adversarial Customer',
            'user_id' => $this->kasir->id,
            'total' => 15000,
            'status' => 'selesai',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => true,
                            'target_module' => 'TRANSACTION',
                            'action_type' => 'REDIRECT',
                            'redirect_url' => '/transaksi',
                            'ai_response' => 'Buka transaksi',
                            'payload' => $this->defaultPayload([
                                'report_type' => 'none',
                            ]),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'cek invoice INV-ADV-999')
            ->call('executeCommand')
            ->assertSee('Detail Transaksi')
            ->assertSee('Invoice: INV-ADV-999');
    }

    public function test_navigation_stock_dapur_redirect_works()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => true,
                            'target_module' => 'GENERAL_CHAT',
                            'action_type' => 'REDIRECT',
                            'redirect_url' => '/stock-dapur',
                            'ai_response' => 'Membuka halaman stock dapur...',
                            'payload' => $this->defaultPayload(),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'buka halaman stock dapur')
            ->call('executeCommand')
            ->assertRedirect('/stock-dapur');
    }

    public function test_navigation_member_redirect_works()
    {
        config(['services.openai.key' => 'mocked-key']);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => true,
                            'target_module' => 'GENERAL_CHAT',
                            'action_type' => 'REDIRECT',
                            'redirect_url' => '/member',
                            'ai_response' => 'Membuka halaman member...',
                            'payload' => $this->defaultPayload(),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'buka halaman member')
            ->call('executeCommand')
            ->assertRedirect('/member');
    }

    public function test_natural_language_matrix_queries()
    {
        config(['services.openai.key' => 'mocked-key']);

        // Setup matrix data
        $u = User::factory()->create(['name' => 'Matrix Member']);
        Member::firstOrCreate(['user_id' => $u->id], [
            'phone' => '089900001111',
            'points' => 33,
            'total_pengeluaran' => 99000,
        ]);

        $kopi = Ingredients::where('nama_bahan', 'Biji Kopi')->first();
        if (! $kopi) {
            Ingredients::create([
                'nama_bahan' => 'Biji Kopi',
                'satuan_id' => $this->satuanKg->id,
                'stok' => 20,
                'hpp' => 5000,
            ]);
        }

        // Fake response returning empty/conversational to let deterministic resolver do the work
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'is_action' => false,
                            'target_module' => 'GENERAL_CHAT',
                            'action_type' => 'none',
                            'ai_response' => '',
                            'payload' => $this->defaultPayload(),
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        // Member count variations
        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'total member')
            ->call('executeCommand')
            ->assertSee('Total member terdaftar:');

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'jumlah member')
            ->call('executeCommand')
            ->assertSee('Total member terdaftar:');

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'lihat semua member')
            ->call('executeCommand')
            ->assertSee('Total member:');

        // Stock queries
        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'stok apa yang habis')
            ->call('executeCommand')
            ->assertSee('habis');

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'stok apa yang menipis')
            ->call('executeCommand')
            ->assertSee('menipis');

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'berapa stok Biji Kopi')
            ->call('executeCommand')
            ->assertSee('Biji Kopi');

        // Payment queries
        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'metode pembayaran apa saja')
            ->call('executeCommand')
            ->assertSee('Metode Pembayaran');

        Livewire::actingAs($this->superadmin)
            ->test(AiCommandCenter::class)
            ->set('commandText', 'apakah QRIS aktif')
            ->call('executeCommand')
            ->assertSee('QRIS');
    }
}
