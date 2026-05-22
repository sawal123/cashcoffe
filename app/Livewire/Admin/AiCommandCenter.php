<?php

namespace App\Livewire\Admin;

use App\Models\Menu;
use App\Models\PriceTier;
use App\Models\SalesChannel;
use App\Models\MenuPrice;
use App\Models\Ingredients;
use App\Models\RiwayatStock;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use App\Models\User;
use App\Models\Absensi;
use App\Models\Branch;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiCommandCenter extends Component
{
    use WithPagination;

    public $commandText = '';
    public $search = '';
    public $perPage = 15;
    public $chatHistory = [];
    
    protected $paginationTheme = 'tailwind';

    public function formatMessage($text)
    {
        // First escape HTML to prevent XSS
        $escaped = e($text);

        // Replace markdown links [Text](Url) with HTML anchor tags
        $pattern = '/\[([^\]]+)\]\(([^)]+)\)/';
        $replacement = '<a href="$2" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold" wire:navigate>$1</a>';
        
        return preg_replace($pattern, $replacement, $escaped);
    }

    public function mount()
    {
        // Strict role protection: only superadmin, manager, or kasir can mount the component
        if (!auth()->check() || (!auth()->user()->hasRole('superadmin') && !auth()->user()->hasRole('manager') && !auth()->user()->hasRole('kasir'))) {
            abort(403, 'Unauthorized action.');
        }

        // Initialize chat history with a welcoming assistant message
        if (empty($this->chatHistory)) {
            $this->chatHistory[] = [
                'sender' => 'ai',
                'text' => 'Halo! Saya POS AI Assistant. Saya siap membantu Anda mengelola sistem kasir. Silakan ajukan pertanyaan atau instruksikan perubahan!',
                'time' => now()->format('H:i')
            ];
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function executeCommand()
    {
        $this->validate([
            'commandText' => 'required|string|max:500',
        ], [
            'commandText.required' => 'Teks perintah wajib diisi.',
            'commandText.max' => 'Teks perintah terlalu panjang (maks. 500 karakter).',
        ]);

        $userQuery = trim($this->commandText);

        // Add user message to history
        $this->chatHistory[] = [
            'sender' => 'user',
            'text' => $userQuery,
            'time' => now()->format('H:i')
        ];

        // Clear the input instantly for superior UX
        $this->commandText = '';

        try {
            $apiKey = config('services.openai.key');
            if (empty($apiKey)) {
                throw new \Exception("OpenAI API Key belum dikonfigurasi di file .env Anda.");
            }

            $model = config('services.openai.model', 'gpt-4o-mini');

            // Dynamically fetch database context
            $dbTiers = PriceTier::pluck('nama_tier')->toArray();
            $dbChannels = SalesChannel::pluck('nama_channel')->toArray();
            $dbBranches = Branch::pluck('nama_cabang')->toArray();

            $tiersString = implode(', ', $dbTiers);
            $channelsString = implode(', ', $dbChannels);
            $branchesString = implode(', ', $dbBranches);

            // Map chat history for OpenAI API (send last 10 turns as context)
            $messages = [];
            
            $context = [
                'user_role' => auth()->check() ? auth()->user()->getRoleNames()->first() : 'guest',
                'user_name' => auth()->check() ? auth()->user()->name : 'Guest',
            ];

            $systemPrompt = "Anda adalah 'POS AI Assistant', asisten internal untuk aplikasi kasir berbasis Laravel Livewire.

Tugas utama Anda:
1. Memahami semua fitur aplikasi.
2. Membantu user menggunakan aplikasi lewat chat.
3. Bisa create, edit, delete, search data.
4. Setelah selesai action, selalu beri direct link.
5. Wajib cek role user sebelum memberi akses.
6. Jangan memberi akses route di luar permission user.
7. Jawab selalu dalam Bahasa Indonesia.

=================================================
ROLE ACCESS
=================================================

Role:
- kasir
- manager
- superadmin

Hak akses:

kasir:
- dashboard
- menu
- order
- meja
- discount
- member
- omset
- pengeluaran
- transaksi
- stock dapur
- asset

manager:
semua hak kasir +
- menu-cabang

superadmin:
semua akses penuh termasuk:
- branch
- sales-channel
- payment-method
- price-tier
- category
- menu-ingredient
- variant-group
- setting
- ai-command-center
- absense
- payroll
- gudang
- riwayat-stock
- user
- riwayat-gudang

Jika user meminta fitur atau mengakses/redirect ke route yang tidak sesuai role, respon:
\"Maaf, Anda tidak memiliki akses ke fitur tersebut.\"

=================================================
ROUTES
=================================================

Dashboard:
GET /dashboard

MENU
List: /menu
Create: /menu/create
Edit: /menu/{menuId}/edit
Variants: /menu/{id}/variants

ORDER
List: /order
Create: /order/create
Edit: /order/{orderId}/edit

MEJA
List: /meja

DISCOUNT
List: /discount
Create: /discount/create
Edit: /discount/{id}/edit
Approval: /discount-approval

MEMBER
List: /member
Create: /member/create
Edit: /member/{memberId}/edit

OMSET
List: /omset

PENGELUARAN
List: /pengeluaran
Create: /pengeluaran/create
Edit: /pengeluaran/{pengeluaranId}/edit

TRANSAKSI
List: /transaksi

STOCK DAPUR
List: /stock-dapur
Create: /stock-dapur/create
Edit: /stock-dapur/{stockId}/edit

ASSET
List: /asset

PRINT STRUK
/print/struk/{id}

EXPORT ORDER
/orders/export

================ SUPERADMIN ================

BRANCH:
/branch

SALES CHANNEL:
/sales-channel

PAYMENT METHOD:
/payment-method

PRICE TIER:
/price-tier

CATEGORY:
/category
/category/create
/category/{categoryId}/edit

MENU INGREDIENT:
/menu-ingredient

VARIANT GROUP:
/variant-group

VARIANT INGREDIENT:
/variant-option/{id}/ingredients

SETTING:
/setting

AI COMMAND CENTER:
/ai-command-center

ABSENSE:
/absense
/absense/requests
/absense/schedule
/absense/shift-master
/absense/{userId}

PAYROLL:
/payroll/generasi

GUDANG:
/gudang
/gudang/create
/gudang/{gudangId}/edit

RIWAYAT STOCK:
/riwayat-stock
/riwayat-stock/create
/riwayat-stock/{stockId}/edit

USER:
/user
/user/create
/user/jabatan
/user/{userId}/edit

RIWAYAT GUDANG:
/riwayat-gudang

================ MANAGER ================

MENU CABANG:
/menu-cabang

=================================================
RULE ACTION
=================================================

CREATE:
- validasi field
- panggil function sesuai module
- balas dengan format:
Status: Berhasil
Aksi: data berhasil dibuat
Link: [url]

EDIT:
- cari id
- update field
- balas dengan format:
Status: Berhasil
Aksi: data berhasil diperbarui
Link: [url]

DELETE:
- minta konfirmasi dulu
- jika ya → delete

SEARCH:
- cari berdasarkan nama/id
- tampilkan hasil

REDIRECT:
- selalu beri direct link

=================================================
FUNCTION CALL FORMAT / JSON Schema instructions:
=================================================
Tentukan properti berikut:
1. 'is_action': true jika user berniat melakukan manipulasi data (CREATE, UPDATE, DELETE) atau pengalihan halaman (REDIRECT). Selain itu, bernilai false.
2. 'target_module': nama modul (contoh: 'MENU', 'ORDER', 'MEMBER', 'DISCOUNT', 'PRICING', 'VARIANT', 'INVENTORY', 'HR_ATTENDANCE', 'EMPLOYEE', 'GENERAL_CHAT').
3. 'action_type': 'CREATE' | 'UPDATE' | 'DELETE' | 'READ' | 'REDIRECT' | 'none'.
4. 'redirect_url': URL tujuan/direct link (misal '/menu/create', '/member/15/edit', dll).
5. 'ai_response': Kalimat jawaban interaktif dalam Bahasa Indonesia. Jika selesai action/redirect, sertakan link url tersebut di dalam respons.
6. 'payload': Objek parameter terdeteksi:
   - menu_name, variant_name, price_tier, sales_channel, price_value, employee_name, shift_name, item_name, branch_name, qty, unit_name, fine_amount, date (dalam format YYYY-MM-DD).

ATURAN PENTING:
- jika user berniat mengubah harga menu di semua tier atau di semua channel, isi price_tier atau sales_channel dengan nilai 'all'.
- jangan mengarang id
- jika data tidak ditemukan → bilang tidak ditemukan
- selalu cek role user. Jika user meminta fitur yang tidak sesuai role, respon: \"Maaf, Anda tidak memiliki akses ke fitur tersebut.\"
- selalu kasih direct link.

Database Context saat ini:
- Price Tiers yang terdaftar: {$tiersString}
- Sales Channels yang terdaftar: {$channelsString}
- Cabang (Branches) yang terdaftar: {$branchesString}";

            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];
            $messages[] = [
                'role' => 'system',
                'content' => 'Current user role: ' . $context['user_role']
            ];

            $slicedHistory = array_slice($this->chatHistory, -10);
            foreach ($slicedHistory as $chat) {
                // Ignore initial greeting to keep context clean
                if (stripos($chat['text'], 'Saya POS AI Assistant') !== false || stripos($chat['text'], 'Saya Kasir Autopilot AI') !== false) {
                    continue;
                }
                $messages[] = [
                    'role' => $chat['sender'] === 'user' ? 'user' : 'assistant',
                    'content' => $chat['text']
                ];
            }

            // Call OpenAI API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'omni_assistant_schema',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'is_action' => [
                                    'type' => 'boolean'
                                ],
                                'target_module' => [
                                    'type' => 'string'
                                ],
                                'action_type' => [
                                    'type' => 'string',
                                    'enum' => ['CREATE', 'UPDATE', 'DELETE', 'READ', 'REDIRECT', 'none']
                                ],
                                'redirect_url' => [
                                    'type' => 'string'
                                ],
                                'ai_response' => [
                                    'type' => 'string'
                                ],
                                'payload' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'menu_name' => [
                                            'type' => 'string'
                                        ],
                                        'variant_name' => [
                                            'type' => 'string'
                                        ],
                                        'price_tier' => [
                                            'type' => 'string'
                                        ],
                                        'sales_channel' => [
                                            'type' => 'string'
                                        ],
                                        'price_value' => [
                                            'type' => 'number'
                                        ],
                                        'employee_name' => [
                                            'type' => 'string'
                                        ],
                                        'shift_name' => [
                                            'type' => 'string'
                                        ],
                                        'item_name' => [
                                            'type' => 'string'
                                        ],
                                        'branch_name' => [
                                            'type' => 'string'
                                        ],
                                        'qty' => [
                                            'type' => 'number'
                                        ],
                                        'unit_name' => [
                                            'type' => 'string'
                                        ],
                                        'fine_amount' => [
                                            'type' => 'number'
                                        ],
                                        'date' => [
                                            'type' => 'string'
                                        ]
                                    ],
                                    'required' => [
                                        'menu_name', 'variant_name', 'price_tier', 'sales_channel', 'price_value',
                                        'employee_name', 'shift_name', 'item_name', 'branch_name', 'qty',
                                        'unit_name', 'fine_amount', 'date'
                                    ],
                                    'additionalProperties' => false
                                ]
                            ],
                            'required' => ['is_action', 'target_module', 'action_type', 'redirect_url', 'ai_response', 'payload'],
                            'additionalProperties' => false
                        ]
                    ]
                ]
            ]);

            if ($response->failed()) {
                $errorMsg = $response->json()['error']['message'] ?? 'Unknown OpenAI API Error';
                throw new \Exception("OpenAI API Gagal: " . $errorMsg);
            }

            $responseData = $response->json();
            $content = $responseData['choices'][0]['message']['content'] ?? null;
            if (!$content) {
                throw new \Exception("Tidak ada respons dari AI.");
            }

            $parsed = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Gagal mendecode respons JSON dari AI.");
            }

            $isAction = (bool) ($parsed['is_action'] ?? false);
            $targetModule = strtoupper($parsed['target_module'] ?? 'GENERAL_CHAT');
            $actionType = strtoupper($parsed['action_type'] ?? 'none');
            $aiResponse = $parsed['ai_response'] ?? '';
            $redirectUrl = $parsed['redirect_url'] ?? '';
            $payload = $parsed['payload'] ?? [];

            $userRole = auth()->check() ? auth()->user()->getRoleNames()->first() : 'guest';

            // Define which roles are allowed to access each module or keyword
            $superadminOnlyModules = [
                'branch', 'sales-channel', 'payment-method', 'price-tier',
                'category', 'menu-ingredient', 'variant-group', 'setting',
                'ai-command-center', 'absense', 'payroll', 'gudang',
                'riwayat-stock', 'user', 'riwayat-gudang', 'pricing', 'variant',
                'inventory', 'hr_attendance', 'employee'
            ];

            $managerOnlyModules = [
                'menu-cabang'
            ];

            // If the user role is kasir, they cannot access superadminOnly or managerOnly modules/URLs
            // If the user role is manager, they cannot access superadminOnly modules/URLs
            $isBlocked = false;

            if ($userRole === 'kasir') {
                foreach ($superadminOnlyModules as $m) {
                    if (strcasecmp($targetModule, $m) === 0 || stripos($redirectUrl, '/' . $m) !== false) {
                        $isBlocked = true;
                        break;
                    }
                }
                foreach ($managerOnlyModules as $m) {
                    if (strcasecmp($targetModule, $m) === 0 || stripos($redirectUrl, '/' . $m) !== false) {
                        $isBlocked = true;
                        break;
                    }
                }
            } elseif ($userRole === 'manager') {
                foreach ($superadminOnlyModules as $m) {
                    if (strcasecmp($targetModule, $m) === 0 || stripos($redirectUrl, '/' . $m) !== false) {
                        $isBlocked = true;
                        break;
                    }
                }
            } elseif ($userRole !== 'superadmin') {
                $isBlocked = true;
            }

            if ($isBlocked) {
                $isAction = false;
                $actionType = 'none';
                $redirectUrl = '';
                $aiResponse = "Maaf, Anda tidak memiliki akses ke fitur tersebut.";
            }

            // Case C: REDIRECT action
            if ($actionType === 'REDIRECT' && !empty($redirectUrl)) {
                $this->chatHistory[] = [
                    'sender' => 'ai',
                    'text' => $aiResponse,
                    'time' => now()->format('H:i')
                ];
                $this->dispatch('chat-message-added');
                return $this->redirect($redirectUrl, navigate: true);
            }

            // Case A: Conversational Turn / READ queries
            if (!$isAction || $actionType === 'READ' || $targetModule === 'GENERAL_CHAT') {
                
                // Real-time Database Selects integration
                if (stripos($userQuery, 'stok') !== false || stripos($userQuery, 'inventory') !== false || stripos($userQuery, 'bahan') !== false) {
                    $items = Ingredients::with('satuan', 'branch')->take(5)->get();
                    if ($items->count() > 0) {
                        $list = $items->map(function($i) {
                            $satuan = $i->satuan->nama_satuan ?? 'pcs';
                            $cabang = $i->branch->nama_cabang ?? 'Pusat';
                            return "- {$i->nama_bahan}: " . number_format($i->stok, 0) . " {$satuan} ({$cabang})";
                        })->implode("\n");
                        $aiResponse .= "\n\nBerikut beberapa data stok saat ini:\n" . $list;
                    } else {
                        $aiResponse .= "\n\nBelum ada data stok bahan baku di database.";
                    }
                } elseif (stripos($userQuery, 'karyawan') !== false || stripos($userQuery, 'absen') !== false || stripos($userQuery, 'pegawai') !== false) {
                    $employees = User::take(5)->get();
                    $list = $employees->map(function($e) {
                        return "- {$e->name} (Role: " . ($e->roles->pluck('name')->implode(', ') ?: 'Karyawan') . ")";
                    })->implode("\n");
                    $aiResponse .= "\n\nBerikut daftar karyawan terdaftar:\n" . $list;
                } elseif (stripos($userQuery, 'harga') !== false || stripos($userQuery, 'menu') !== false || stripos($userQuery, 'tier') !== false) {
                    $menuPrices = MenuPrice::with('menu', 'priceTier', 'salesChannel')->take(5)->get();
                    if ($menuPrices->count() > 0) {
                        $list = $menuPrices->map(function($p) {
                            $menu = $p->menu->nama_menu ?? 'Menu';
                            $tier = $p->priceTier->nama_tier ?? 'Reguler';
                            $channel = $p->salesChannel->nama_channel ?? 'Dine In';
                            return "- {$menu} ({$tier} / {$channel}): Rp" . number_format($p->harga, 0, ',', '.');
                        })->implode("\n");
                        $aiResponse .= "\n\nBerikut beberapa daftar harga menu:\n" . $list;
                    }
                } elseif (stripos($userQuery, 'laris') !== false || stripos($userQuery, 'terlaris') !== false || stripos($userQuery, 'laku') !== false || stripos($userQuery, 'populer') !== false) {
                    $topMenus = \App\Models\PesananItem::selectRaw('menus_id, SUM(qty) as total_qty')
                        ->groupBy('menus_id')
                        ->orderByDesc('total_qty')
                        ->with('menu')
                        ->take(5)
                        ->get();

                    if ($topMenus->count() > 0) {
                        $list = $topMenus->map(function($item, $index) {
                            $name = $item->menu->nama_menu ?? 'Menu Tidak Diketahui';
                            return ($index + 1) . ". {$name} (Terjual: " . number_format($item->total_qty, 0) . " pcs)";
                        })->implode("\n");

                        $aiResponse = "Tentu! Berikut adalah daftar 5 menu paling laris/terpopuler berdasarkan total kuantitas penjualan di database:\n\n" . $list;
                    } else {
                        $aiResponse = "Belum ada transaksi penjualan yang tercatat di database untuk menentukan menu terlaris.";
                    }
                }

                $this->chatHistory[] = [
                    'sender' => 'ai',
                    'text' => $aiResponse,
                    'redirect_url' => $redirectUrl,
                    'time' => now()->format('H:i')
                ];

            } else {
                // Case B: CRUD Actions Switch-Case Router
                switch ($targetModule) {
                    case 'PRICING':
                        if ($actionType === 'UPDATE') {
                            $menuName = trim($payload['menu_name'] ?? '');
                            $priceTier = trim($payload['price_tier'] ?? '');
                            $salesChannel = trim($payload['sales_channel'] ?? '');
                            $priceValue = (float) ($payload['price_value'] ?? 0);

                            if (empty($menuName)) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Nama menu tidak terdeteksi. " . $aiResponse,
                                    'time' => now()->format('H:i')
                                ];
                                break;
                            }

                            if ($priceValue <= 0) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Harga baru tidak valid. " . $aiResponse,
                                    'time' => now()->format('H:i')
                                ];
                                break;
                            }

                            $menu = Menu::where('nama_menu', 'LIKE', '%' . $menuName . '%')->first();
                            if (!$menu) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Menu '{$menuName}' tidak ditemukan.",
                                    'time' => now()->format('H:i')
                                ];
                                break;
                            }

                            // Determine Tiers to update
                            $tiersToUpdate = [];
                            if (strcasecmp($priceTier, 'all') === 0 || strcasecmp($priceTier, 'semua') === 0) {
                                $tiersToUpdate = PriceTier::all();
                            } elseif (!empty($priceTier)) {
                                $t = PriceTier::where('nama_tier', 'LIKE', '%' . $priceTier . '%')->first();
                                if ($t) {
                                    $tiersToUpdate[] = $t;
                                }
                            }

                            if (count($tiersToUpdate) === 0) {
                                $availableTiers = PriceTier::pluck('nama_tier')->implode(', ');
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Mau tier mana yang diubah? (Tier yang tersedia: {$availableTiers})",
                                    'time' => now()->format('H:i')
                                ];
                                break;
                            }

                            // Determine Channels to update
                            $channelsToUpdate = [];
                            if (strcasecmp($salesChannel, 'all') === 0 || strcasecmp($salesChannel, 'semua') === 0) {
                                $channelsToUpdate = SalesChannel::all();
                            } elseif (!empty($salesChannel)) {
                                $c = SalesChannel::where('nama_channel', 'LIKE', '%' . $salesChannel . '%')->first();
                                if ($c) {
                                    $channelsToUpdate[] = $c;
                                }
                            }

                            if (count($channelsToUpdate) === 0) {
                                $availableChannels = SalesChannel::pluck('nama_channel')->implode(', ');
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Mau sales channel mana yang diubah? (Channel yang tersedia: {$availableChannels})",
                                    'time' => now()->format('H:i')
                                ];
                                break;
                            }

                            // Update or Create matching rows
                            foreach ($tiersToUpdate as $tObj) {
                                foreach ($channelsToUpdate as $cObj) {
                                    MenuPrice::updateOrCreate(
                                        [
                                            'menu_id' => $menu->id,
                                            'price_tier_id' => $tObj->id,
                                            'sales_channel_id' => $cObj->id,
                                        ],
                                        ['harga' => $priceValue]
                                    );
                                }
                            }

                            $this->chatHistory[] = [
                                'sender' => 'ai',
                                'text' => $aiResponse,
                                'time' => now()->format('H:i')
                            ];
                            $this->dispatch('showToast', message: $aiResponse, type: 'success', title: 'Berhasil');
                        }
                        break;

                    case 'HR_ATTENDANCE':
                        if ($actionType === 'CREATE') {
                            $employeeName = trim($payload['employee_name'] ?? '');
                            $fineAmount = (float) ($payload['fine_amount'] ?? 0);
                            $date = trim($payload['date'] ?? '') ?: now()->toDateString();

                            if (empty($employeeName)) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Nama karyawan wajib ditentukan untuk pengenaan denda.",
                                    'time' => now()->format('H:i')
                                ];
                                break;
                            }

                            $employee = User::where('name', 'LIKE', '%' . $employeeName . '%')->first();
                            if (!$employee) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Karyawan bernama '{$employeeName}' tidak ditemukan.",
                                    'time' => now()->format('H:i')
                                ];
                                break;
                            }

                            // Update or create Absensi record to store the late fine amount
                            Absensi::updateOrCreate(
                                [
                                    'user_id' => $employee->id,
                                    'tanggal' => $date,
                                ],
                                [
                                    'jam_masuk' => '08:00:00', // Default check-in time
                                    'status' => 'terlambat',
                                    'denda_missing_clockout' => $fineAmount,
                                    'keterangan' => 'Denda telat diset manual oleh AI Command Center Autopilot'
                                ]
                            );

                            $this->chatHistory[] = [
                                'sender' => 'ai',
                                'text' => $aiResponse,
                                'time' => now()->format('H:i')
                            ];
                            $this->dispatch('showToast', message: $aiResponse, type: 'success', title: 'Berhasil');
                        }
                        break;

                    case 'INVENTORY':
                        if ($actionType === 'UPDATE') {
                            $itemName = trim($payload['item_name'] ?? '');
                            $branchName = trim($payload['branch_name'] ?? '');
                            $qty = (float) ($payload['qty'] ?? 0);

                            if (empty($itemName)) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Nama bahan baku wajib ditentukan.",
                                    'time' => now()->format('H:i')
                                ];
                                break;
                            }

                            // Find Branch
                            $branch = null;
                            if (!empty($branchName)) {
                                $branch = Branch::where('nama_cabang', 'LIKE', '%' . $branchName . '%')->first();
                            }
                            if (!$branch) {
                                $availableBranches = Branch::pluck('nama_cabang')->implode(', ');
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Cabang tidak terdeteksi. Cabang yang tersedia: {$availableBranches}",
                                    'time' => now()->format('H:i')
                                ];
                                break;
                            }

                            // Find Ingredient in that branch
                            $ingredient = Ingredients::where('nama_bahan', 'LIKE', '%' . $itemName . '%')
                                ->where('branch_id', $branch->id)
                                ->first();

                            if (!$ingredient) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Bahan baku '{$itemName}' tidak ditemukan di cabang '{$branch->nama_cabang}'.",
                                    'time' => now()->format('H:i')
                                ];
                                break;
                            }

                            $qtyBefore = $ingredient->stok;
                            $diff = $qty - $qtyBefore;
                            $tipe = $diff >= 0 ? 'in' : 'out';

                            $ingredient->update(['stok' => $qty]);

                            RiwayatStock::create([
                                'ingredient_id' => $ingredient->id,
                                'branch_id' => $branch->id,
                                'qty' => abs($diff),
                                'qty_before' => $qtyBefore,
                                'qty_after' => $qty,
                                'tipe' => $tipe,
                                'keterangan' => 'Penyesuaian stok via AI Command Center Autopilot'
                            ]);

                            $this->chatHistory[] = [
                                'sender' => 'ai',
                                'text' => $aiResponse,
                                'time' => now()->format('H:i')
                            ];
                            $this->dispatch('showToast', message: $aiResponse, type: 'success', title: 'Berhasil');
                        }
                        break;

                    case 'VARIANT':
                        if ($actionType === 'CREATE') {
                            $menuName = trim($payload['menu_name'] ?? '');
                            $variantName = trim($payload['variant_name'] ?? '');

                            if (empty($menuName) || empty($variantName)) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Nama menu dan nama varian wajib ditentukan.",
                                    'time' => now()->format('H:i')
                                ];
                                break;
                            }

                            $menu = Menu::where('nama_menu', 'LIKE', '%' . $menuName . '%')->first();
                            if (!$menu) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Menu '{$menuName}' tidak ditemukan.",
                                    'time' => now()->format('H:i')
                                ];
                                break;
                            }

                            // Find or create variant group (e.g. Suhu or Ukuran depending on variant name)
                            $groupName = 'Varian';
                            if (stripos($variantName, 'large') !== false || stripos($variantName, 'medium') !== false || stripos($variantName, 'small') !== false || stripos($variantName, 'ukuran') !== false) {
                                $groupName = 'Ukuran';
                            } elseif (stripos($variantName, 'hot') !== false || stripos($variantName, 'ice') !== false || stripos($variantName, 'suhu') !== false) {
                                $groupName = 'Suhu';
                            }

                            $group = VariantGroup::where('nama_group', 'LIKE', '%' . $groupName . '%')->first();
                            if (!$group) {
                                $group = VariantGroup::create([
                                    'nama_group' => $groupName,
                                    'selection_type' => 'single',
                                    'is_required' => false
                                ]);
                            }

                            VariantOption::create([
                                'variant_group_id' => $group->id,
                                'nama_opsi' => $variantName,
                                'extra_price' => 0
                            ]);

                            if (!$menu->variantGroups()->where('variant_groups.id', $group->id)->exists()) {
                                $menu->variantGroups()->attach($group->id);
                            }

                            $this->chatHistory[] = [
                                'sender' => 'ai',
                                'text' => $aiResponse,
                                'time' => now()->format('H:i')
                            ];
                            $this->dispatch('showToast', message: $aiResponse, type: 'success', title: 'Berhasil');
                        }
                        break;

                    default:
                        $this->chatHistory[] = [
                            'sender' => 'ai',
                            'text' => $aiResponse,
                            'time' => now()->format('H:i')
                        ];
                        break;
                }
            }

            // Dispatch scroll and reactive component refresh events
            $this->dispatch('chat-message-added');
            $this->dispatch('refreshComponent');

        } catch (\Exception $e) {
            Log::error("AI Command Center Error: " . $e->getMessage());
            $this->chatHistory[] = [
                'sender' => 'ai',
                'text' => "Maaf, sistem mengalami kesalahan: " . $e->getMessage(),
                'time' => now()->format('H:i')
            ];
            $this->dispatch('showToast', message: $e->getMessage(), type: 'error', title: 'Gagal');
            $this->dispatch('chat-message-added');
        }
    }

    public function render()
    {
        // Get all menu prices with pagination for live preview
        $prices = MenuPrice::with(['menu', 'priceTier', 'salesChannel'])
            ->when(!empty($this->search), function ($query) {
                $query->whereHas('menu', function ($q) {
                    $q->where('nama_menu', 'LIKE', '%' . $this->search . '%');
                })
                ->orWhereHas('priceTier', function ($q) {
                    $q->where('nama_tier', 'LIKE', '%' . $this->search . '%');
                })
                ->orWhereHas('salesChannel', function ($q) {
                    $q->where('nama_channel', 'LIKE', '%' . $this->search . '%');
                });
            })
            ->orderBy('updated_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.admin.ai-command-center', [
            'prices' => $prices,
            'title' => 'AI Command Center Autopilot'
        ])->layout('layouts.app', ['title' => 'AI Command Center']);
    }
}
