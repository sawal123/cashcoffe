<?php

namespace Tests\Feature;

use App\Livewire\Admin\AiCommandCenter;
use App\Models\AiChatHistory;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Ingredients;
use App\Models\Menu;
use App\Models\PriceTier;
use App\Models\SalesChannel;
use App\Models\SatuanBahan;
use App\Models\User;
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
            ->assertSee('Sip! Harga Kopi Susu')
            ->assertSee('Rp25.000')
            ->assertDispatched('showToast');

        $this->assertDatabaseHas('menu_prices', [
            'menu_id' => $this->menu->id,
            'price_tier_id' => $this->tier->id,
            'sales_channel_id' => $this->channel->id,
            'harga' => 25000,
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
}
