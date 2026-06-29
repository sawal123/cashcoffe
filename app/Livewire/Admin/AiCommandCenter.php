<?php

namespace App\Livewire\Admin;

use App\Models\Absensi;
use App\Models\AiChatHistory;
use App\Models\Branch;
use App\Models\Ingredients;
use App\Models\Menu;
use App\Models\MenuPrice;
use App\Models\PriceTier;
use App\Models\RiwayatStock;
use App\Models\SalesChannel;
use App\Models\User;
use App\Models\VariantGroup;
use App\Models\VariantOption;
use App\Services\AiDatabaseQueryService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class AiCommandCenter extends Component
{
    use WithPagination;

    public $commandText = '';

    public $search = '';

    public $perPage = 15;

    public $chatHistory = [];

    public $chatSessions = [];

    public $activeChatId = null;

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
        if (! auth()->check() || (! auth()->user()->hasRole('superadmin') && ! auth()->user()->hasRole('manager') && ! auth()->user()->hasRole('kasir'))) {
            abort(403, 'Unauthorized action.');
        }

        $latestChat = AiChatHistory::query()
            ->where('user_id', auth()->id())
            ->latest('updated_at')
            ->first();

        if ($latestChat) {
            $this->activeChatId = $latestChat->id;
            $this->chatHistory = $latestChat->messages ?? [];

            if (empty($this->chatHistory)) {
                $this->chatHistory[] = $this->greetingMessage();
                $this->persistChatHistory();
            }
        } else {
            $this->startNewChat(false);
        }

        $this->refreshChatSessions();
    }

    public function startNewChat(bool $dispatchEvent = true): void
    {
        if (! auth()->check()) {
            return;
        }

        $history = AiChatHistory::create([
            'user_id' => auth()->id(),
            'title' => 'Chat baru',
            'messages' => [$this->greetingMessage()],
        ]);

        $this->activeChatId = $history->id;
        $this->chatHistory = $history->messages;
        $this->refreshChatSessions();

        if ($dispatchEvent) {
            $this->dispatch('chat-message-added');
        }
    }

    public function loadChat(int $chatId): void
    {
        $history = AiChatHistory::query()
            ->where('user_id', auth()->id())
            ->findOrFail($chatId);

        $this->activeChatId = $history->id;
        $this->chatHistory = $history->messages ?? [];

        if (empty($this->chatHistory)) {
            $this->chatHistory[] = $this->greetingMessage();
            $this->persistChatHistory();
        }

        $this->refreshChatSessions();
        $this->dispatch('chat-message-added');
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
            'time' => now()->format('H:i'),
        ];
        $this->updateActiveChatTitle($userQuery);

        // Clear the input instantly for superior UX
        $this->commandText = '';

        try {
            $apiKey = config('services.openai.key');
            if (empty($apiKey)) {
                throw new \Exception('OpenAI API Key belum dikonfigurasi di file .env Anda.');
            }

            $model = config('services.openai.model', 'gpt-4o-mini');

            // Dynamically fetch database context
            $dbMenus = Menu::pluck('nama_menu')->toArray();
            $dbTiers = PriceTier::pluck('nama_tier')->toArray();
            $dbChannels = SalesChannel::pluck('nama_channel')->toArray();
            $dbBranches = Branch::pluck('nama_cabang')->toArray();
            $dbIngredients = Ingredients::pluck('nama_bahan')->toArray();
            $dbEmployees = User::pluck('name')->toArray();

            $menusString = implode(', ', $dbMenus);
            $tiersString = implode(', ', $dbTiers);
            $channelsString = implode(', ', $dbChannels);
            $branchesString = implode(', ', $dbBranches);
            $ingredientsString = implode(', ', $dbIngredients);
            $employeesString = implode(', ', $dbEmployees);

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
4. Untuk pertanyaan laporan, klasifikasikan jenis laporan dan filter. Laravel akan membaca database; jangan mengarang angka.
4. Setelah selesai action, selalu beri direct link.
5. Wajib cek role user sebelum memberi akses.
6. Jangan memberi akses route di luar permission user.
7. Jawab selalu dalam Bahasa Indonesia.

BATAS TOPIK:
- Hanya layani topik yang berhubungan dengan aplikasi Cash Coffee: penjualan, transaksi, menu, harga, stok, cabang, karyawan, absensi, payroll, pengeluaran, member, diskon, serta cara memakai aplikasi.
- Untuk politik, hiburan, pengetahuan umum, coding yang tidak terkait aplikasi, kesehatan, atau topik lain di luar aplikasi, set is_in_scope=false, target_module='OUT_OF_SCOPE', dan jangan menjawab substansi pertanyaannya.

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
    - report_type: 'menu_sales' | 'top_selling_menus' | 'least_selling_menus' | 'sales_summary' | 'inventory_stock' | 'employee_attendance' | 'none'.
    - menu_name, variant_name, price_tier, sales_channel, price_value, employee_name, shift_name, item_name, branch_name, qty, unit_name, fine_amount, date.
    - date_from dan date_to dalam format YYYY-MM-DD. Hari ini adalah ".now()->toDateString().".
    - limit untuk jumlah hasil peringkat, maksimum 10.

CONTOH LAPORAN:
- 'berapa penjualan Sanger' => action_type=READ, target_module='SALES_REPORT', report_type='menu_sales', menu_name='Sanger'.
- 'menu paling laris bulan ini' => report_type='top_selling_menus', date_from dan date_to isi rentang bulan berjalan.
- 'menu paling sepi/sedikit dipesan' => report_type='least_selling_menus', date_from dan date_to isi rentang bulan berjalan atau kosongkan.
- 'berapa omzet hari ini' => report_type='sales_summary', date_from=date_to=hari ini.
- 'stok biji kopi di Medan' => report_type='inventory_stock', item_name='biji kopi', branch_name='Medan'.
- 'absensi Budi bulan ini' => report_type='employee_attendance', employee_name='Budi', isi periode bulan berjalan.

ATURAN PENTING:
- jika user berniat mengubah harga menu di semua tier atau di semua channel, isi price_tier atau sales_channel dengan nilai 'all'.
- jangan mengarang id
- jika data tidak ditemukan → bilang tidak ditemukan
- selalu cek role user. Jika user meminta fitur yang tidak sesuai role, respon: 'Maaf, Anda tidak memiliki akses ke fitur tersebut.'
- selalu kasih direct link.

Database Context saat ini:
- Menu yang terdaftar: {$menusString}
- Price Tiers yang terdaftar: {$tiersString}
- Sales Channels yang terdaftar: {$channelsString}
- Cabang (Branches) yang terdaftar: {$branchesString}
- Bahan Baku / Stok (Ingredients) yang terdaftar: {$ingredientsString}
- Karyawan (Employees) yang terdaftar: {$employeesString}";

            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
            $messages[] = [
                'role' => 'system',
                'content' => 'Current user role: '.$context['user_role'],
            ];

            $slicedHistory = array_slice($this->chatHistory, -10);
            foreach ($slicedHistory as $chat) {
                // Ignore initial greeting to keep context clean
                if (stripos($chat['text'], 'Saya POS AI Assistant') !== false || stripos($chat['text'], 'Saya Kasir Autopilot AI') !== false) {
                    continue;
                }
                $messages[] = [
                    'role' => $chat['sender'] === 'user' ? 'user' : 'assistant',
                    'content' => $chat['text'],
                ];
            }

            // Call OpenAI API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
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
                                    'type' => 'boolean',
                                ],
                                'is_in_scope' => [
                                    'type' => 'boolean',
                                ],
                                'target_module' => [
                                    'type' => 'string',
                                ],
                                'action_type' => [
                                    'type' => 'string',
                                    'enum' => ['CREATE', 'UPDATE', 'DELETE', 'READ', 'REDIRECT', 'none'],
                                ],
                                'redirect_url' => [
                                    'type' => 'string',
                                ],
                                'ai_response' => [
                                    'type' => 'string',
                                ],
                                'payload' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'menu_name' => [
                                            'type' => 'string',
                                        ],
                                        'variant_name' => [
                                            'type' => 'string',
                                        ],
                                        'price_tier' => [
                                            'type' => 'string',
                                        ],
                                        'sales_channel' => [
                                            'type' => 'string',
                                        ],
                                        'price_value' => [
                                            'type' => 'number',
                                        ],
                                        'employee_name' => [
                                            'type' => 'string',
                                        ],
                                        'shift_name' => [
                                            'type' => 'string',
                                        ],
                                        'item_name' => [
                                            'type' => 'string',
                                        ],
                                        'branch_name' => [
                                            'type' => 'string',
                                        ],
                                        'qty' => [
                                            'type' => 'number',
                                        ],
                                        'unit_name' => [
                                            'type' => 'string',
                                        ],
                                        'fine_amount' => [
                                            'type' => 'number',
                                        ],
                                        'date' => [
                                            'type' => 'string',
                                        ],
                                        'report_type' => [
                                            'type' => 'string',
                                            'enum' => ['menu_sales', 'top_selling_menus', 'least_selling_menus', 'sales_summary', 'inventory_stock', 'employee_attendance', 'menu_ingredients', 'none'],
                                        ],
                                        'date_from' => [
                                            'type' => 'string',
                                        ],
                                        'date_to' => [
                                            'type' => 'string',
                                        ],
                                        'limit' => [
                                            'type' => 'number',
                                        ],
                                    ],
                                    'required' => [
                                        'menu_name', 'variant_name', 'price_tier', 'sales_channel', 'price_value',
                                        'employee_name', 'shift_name', 'item_name', 'branch_name', 'qty',
                                        'unit_name', 'fine_amount', 'date', 'report_type', 'date_from', 'date_to', 'limit',
                                    ],
                                    'additionalProperties' => false,
                                ],
                            ],
                            'required' => ['is_action', 'is_in_scope', 'target_module', 'action_type', 'redirect_url', 'ai_response', 'payload'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
            ]);

            if ($response->failed()) {
                $errorMsg = $response->json()['error']['message'] ?? 'Unknown OpenAI API Error';
                throw new \Exception('OpenAI API Gagal: '.$errorMsg);
            }

            $responseData = $response->json();
            $content = $responseData['choices'][0]['message']['content'] ?? null;
            if (! $content) {
                throw new \Exception('Tidak ada respons dari AI.');
            }

            $parsed = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Gagal mendecode respons JSON dari AI.');
            }

            $isAction = (bool) ($parsed['is_action'] ?? false);
            $targetModule = strtoupper($parsed['target_module'] ?? 'GENERAL_CHAT');
            $actionType = strtoupper($parsed['action_type'] ?? 'none');
            $aiResponse = $parsed['ai_response'] ?? '';
            $redirectUrl = $parsed['redirect_url'] ?? '';
            $payload = $parsed['payload'] ?? [];
            $isInScope = (bool) ($parsed['is_in_scope'] ?? true);

            $userRole = auth()->check() ? auth()->user()->getRoleNames()->first() : 'guest';

            // Define which roles are allowed to access each module or keyword
            $superadminOnlyModules = [
                'branch', 'sales-channel', 'payment-method', 'price-tier',
                'category', 'menu-ingredient', 'variant-group', 'setting',
                'ai-command-center', 'absense', 'payroll', 'gudang',
                'riwayat-stock', 'user', 'riwayat-gudang', 'pricing', 'variant',
                'inventory', 'hr_attendance', 'employee',
            ];

            $managerOnlyModules = [
                'menu-cabang',
            ];

            // If the user role is kasir, they cannot access superadminOnly or managerOnly modules/URLs
            // If the user role is manager, they cannot access superadminOnly modules/URLs
            $isBlocked = false;

            if ($userRole === 'kasir') {
                foreach ($superadminOnlyModules as $m) {
                    if (strcasecmp($targetModule, $m) === 0 || stripos($redirectUrl, '/'.$m) !== false) {
                        $isBlocked = true;
                        break;
                    }
                }
                foreach ($managerOnlyModules as $m) {
                    if (strcasecmp($targetModule, $m) === 0 || stripos($redirectUrl, '/'.$m) !== false) {
                        $isBlocked = true;
                        break;
                    }
                }
            } elseif ($userRole === 'manager') {
                foreach ($superadminOnlyModules as $m) {
                    if (strcasecmp($targetModule, $m) === 0 || stripos($redirectUrl, '/'.$m) !== false) {
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
                $aiResponse = 'Maaf, Anda tidak memiliki akses ke fitur tersebut.';
            }

            if (! $isInScope || $targetModule === 'OUT_OF_SCOPE') {
                $this->chatHistory[] = [
                    'sender' => 'ai',
                    'text' => 'Maaf, saya hanya dapat membantu pertanyaan dan pekerjaan yang berkaitan dengan aplikasi Cash Coffee.',
                    'time' => now()->format('H:i'),
                ];
                $this->dispatch('chat-message-added');

                return;
            }

            // Case C: REDIRECT action
            if ($actionType === 'REDIRECT' && ! empty($redirectUrl)) {
                $this->chatHistory[] = [
                    'sender' => 'ai',
                    'text' => $aiResponse,
                    'time' => now()->format('H:i'),
                ];
                $this->dispatch('chat-message-added');

                return $this->redirect($redirectUrl, navigate: true);
            }

            // Case A: Conversational Turn / READ queries
            if (! $isAction || $actionType === 'READ' || $targetModule === 'GENERAL_CHAT') {
                $reportType = $this->resolveReportType($payload, $userQuery);

                if ($reportType !== 'none') {
                    $databaseAnswer = (new AiDatabaseQueryService)->answer($reportType, $payload, $userQuery);

                    if ($databaseAnswer !== null) {
                        $aiResponse = $databaseAnswer;
                    }
                }

                // Real-time Database Selects integration
                if ($reportType === 'none' && (stripos($userQuery, 'stok') !== false || stripos($userQuery, 'inventory') !== false || stripos($userQuery, 'bahan') !== false)) {
                    $items = Ingredients::with('satuan', 'branch')->take(5)->get();
                    if ($items->count() > 0) {
                        $list = $items->map(function ($i) {
                            $satuan = $i->satuan->nama_satuan ?? 'pcs';
                            $cabang = $i->branch->nama_cabang ?? 'Pusat';

                            return "- {$i->nama_bahan}: ".number_format($i->stok, 0)." {$satuan} ({$cabang})";
                        })->implode("\n");
                        $aiResponse .= "\n\nBerikut beberapa data stok saat ini:\n".$list;
                    } else {
                        $aiResponse .= "\n\nBelum ada data stok bahan baku di database.";
                    }
                } elseif ($reportType === 'none' && (stripos($userQuery, 'karyawan') !== false || stripos($userQuery, 'absen') !== false || stripos($userQuery, 'pegawai') !== false)) {
                    $employees = User::take(5)->get();
                    $list = $employees->map(function ($e) {
                        return "- {$e->name} (Role: ".($e->roles->pluck('name')->implode(', ') ?: 'Karyawan').')';
                    })->implode("\n");
                    $aiResponse .= "\n\nBerikut daftar karyawan terdaftar:\n".$list;
                } elseif ($reportType === 'none' && (stripos($userQuery, 'harga') !== false || stripos($userQuery, 'daftar harga') !== false || stripos($userQuery, 'price') !== false || stripos($userQuery, 'tier') !== false || stripos($userQuery, 'biaya') !== false)) {
                    $menuPrices = MenuPrice::with('menu', 'priceTier', 'salesChannel')->take(5)->get();
                    if ($menuPrices->count() > 0) {
                        $list = $menuPrices->map(function ($p) {
                            $menu = $p->menu->nama_menu ?? 'Menu';
                            $tier = $p->priceTier->nama_tier ?? 'Reguler';
                            $channel = $p->salesChannel->nama_channel ?? 'Dine In';

                            return "- {$menu} ({$tier} / {$channel}): Rp".number_format($p->harga, 0, ',', '.');
                        })->implode("\n");
                        $aiResponse .= "\n\nBerikut beberapa daftar harga menu:\n".$list;
                    }
                } elseif ($reportType === 'none' && (stripos($userQuery, 'laris') !== false || stripos($userQuery, 'terlaris') !== false || stripos($userQuery, 'laku') !== false || stripos($userQuery, 'populer') !== false)) {
                    $topMenus = \App\Models\PesananItem::selectRaw('menus_id, SUM(qty) as total_qty')
                        ->groupBy('menus_id')
                        ->orderByDesc('total_qty')
                        ->with('menu')
                        ->take(5)
                        ->get();

                    if ($topMenus->count() > 0) {
                        $list = $topMenus->map(function ($item, $index) {
                            $name = $item->menu->nama_menu ?? 'Menu Tidak Diketahui';

                            return ($index + 1).". {$name} (Terjual: ".number_format($item->total_qty, 0).' pcs)';
                        })->implode("\n");

                        $aiResponse = "Tentu! Berikut adalah daftar 5 menu paling laris/terpopuler berdasarkan total kuantitas penjualan di database:\n\n".$list;
                    } else {
                        $aiResponse = 'Belum ada transaksi penjualan yang tercatat di database untuk menentukan menu terlaris.';
                    }
                }

                $this->chatHistory[] = [
                    'sender' => 'ai',
                    'text' => $aiResponse,
                    'redirect_url' => $redirectUrl,
                    'time' => now()->format('H:i'),
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
                                    'text' => 'Nama menu tidak terdeteksi. '.$aiResponse,
                                    'time' => now()->format('H:i'),
                                ];
                                break;
                            }

                            if ($priceValue <= 0) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => 'Harga baru tidak valid. '.$aiResponse,
                                    'time' => now()->format('H:i'),
                                ];
                                break;
                            }

                            $menu = Menu::where('nama_menu', 'LIKE', '%'.$menuName.'%')->first();
                            if (! $menu) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Menu '{$menuName}' tidak ditemukan.",
                                    'time' => now()->format('H:i'),
                                ];
                                break;
                            }

                            $targets = $this->resolvePricingTargets($priceTier, $salesChannel, $menu);

                            if (! empty($targets['error'])) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => $targets['error'],
                                    'time' => now()->format('H:i'),
                                ];
                                break;
                            }

                            $tiersToUpdate = $targets['tiers'];
                            $channelsToUpdate = $targets['channels'];
                            $updatedRows = [];

                            foreach ($tiersToUpdate as $tObj) {
                                foreach ($channelsToUpdate as $cObj) {
                                    $price = MenuPrice::query()
                                        ->where('menu_id', $menu->id)
                                        ->where('price_tier_id', $tObj->id)
                                        ->where('sales_channel_id', $cObj->id)
                                        ->first();

                                    if (! $price && $this->isDefaultDineInChannel($cObj)) {
                                        $price = MenuPrice::query()
                                            ->where('menu_id', $menu->id)
                                            ->where('price_tier_id', $tObj->id)
                                            ->whereNull('sales_channel_id')
                                            ->first();
                                    }

                                    if ($price) {
                                        $price->update([
                                            'sales_channel_id' => $cObj->id,
                                            'harga' => $priceValue,
                                        ]);
                                    } else {
                                        $price = MenuPrice::create([
                                            'menu_id' => $menu->id,
                                            'price_tier_id' => $tObj->id,
                                            'sales_channel_id' => $cObj->id,
                                            'harga' => $priceValue,
                                            'h_promo' => 0,
                                        ]);
                                    }

                                    if ($this->isDefaultBasePriceTarget($tObj, $cObj)) {
                                        $menu->update(['harga' => $priceValue]);
                                    }

                                    $updatedRows[] = [
                                        'tier' => $tObj->nama_tier,
                                        'channel' => $cObj->nama_channel,
                                        'price_id' => $price->id,
                                    ];
                                }
                            }

                            $verifiedCount = MenuPrice::query()
                                ->whereIn('id', collect($updatedRows)->pluck('price_id')->all())
                                ->where('harga', $priceValue)
                                ->count();

                            if ($verifiedCount !== count($updatedRows)) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => 'Status: Gagal'."\n".'Aksi: Harga belum berhasil diverifikasi di database, jadi perubahan tidak saya klaim berhasil.',
                                    'time' => now()->format('H:i'),
                                ];
                                $this->dispatch('showToast', message: 'Harga belum berhasil diverifikasi.', type: 'error', title: 'Gagal');
                                break;
                            }

                            $aiResponse = $this->pricingSuccessMessage($menu, $priceValue, $updatedRows, $targets['notes']);

                            $this->chatHistory[] = [
                                'sender' => 'ai',
                                'text' => $aiResponse,
                                'redirect_url' => '/menu/'.$menu->id.'/edit',
                                'time' => now()->format('H:i'),
                            ];
                            $this->dispatch('showToast', message: 'Harga menu berhasil diperbarui dan diverifikasi.', type: 'success', title: 'Berhasil');
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
                                    'text' => 'Nama karyawan wajib ditentukan untuk pengenaan denda.',
                                    'time' => now()->format('H:i'),
                                ];
                                break;
                            }

                            $employee = User::where('name', 'LIKE', '%'.$employeeName.'%')->first();
                            if (! $employee) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Karyawan bernama '{$employeeName}' tidak ditemukan.",
                                    'time' => now()->format('H:i'),
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
                                    'keterangan' => 'Denda telat diset manual oleh AI Command Center Autopilot',
                                ]
                            );

                            $this->chatHistory[] = [
                                'sender' => 'ai',
                                'text' => $aiResponse,
                                'time' => now()->format('H:i'),
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
                                    'text' => 'Nama bahan baku wajib ditentukan.',
                                    'time' => now()->format('H:i'),
                                ];
                                break;
                            }

                            // Find Branch
                            $branch = null;
                            if (! empty($branchName)) {
                                $branch = Branch::where('nama_cabang', 'LIKE', '%'.$branchName.'%')->first();
                            }
                            if (! $branch) {
                                $availableBranches = Branch::pluck('nama_cabang')->implode(', ');
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Cabang tidak terdeteksi. Cabang yang tersedia: {$availableBranches}",
                                    'time' => now()->format('H:i'),
                                ];
                                break;
                            }

                            // Find Ingredient in that branch
                            $ingredient = Ingredients::where('nama_bahan', 'LIKE', '%'.$itemName.'%')
                                ->where('branch_id', $branch->id)
                                ->first();

                            if (! $ingredient) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Bahan baku '{$itemName}' tidak ditemukan di cabang '{$branch->nama_cabang}'.",
                                    'time' => now()->format('H:i'),
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
                                'keterangan' => 'Penyesuaian stok via AI Command Center Autopilot',
                            ]);

                            $this->chatHistory[] = [
                                'sender' => 'ai',
                                'text' => $aiResponse,
                                'time' => now()->format('H:i'),
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
                                    'text' => 'Nama menu dan nama varian wajib ditentukan.',
                                    'time' => now()->format('H:i'),
                                ];
                                break;
                            }

                            $menu = Menu::where('nama_menu', 'LIKE', '%'.$menuName.'%')->first();
                            if (! $menu) {
                                $this->chatHistory[] = [
                                    'sender' => 'ai',
                                    'text' => "Menu '{$menuName}' tidak ditemukan.",
                                    'time' => now()->format('H:i'),
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

                            $group = VariantGroup::where('nama_group', 'LIKE', '%'.$groupName.'%')->first();
                            if (! $group) {
                                $group = VariantGroup::create([
                                    'nama_group' => $groupName,
                                    'selection_type' => 'single',
                                    'is_required' => false,
                                ]);
                            }

                            VariantOption::create([
                                'variant_group_id' => $group->id,
                                'nama_opsi' => $variantName,
                                'extra_price' => 0,
                            ]);

                            if (! $menu->variantGroups()->where('variant_groups.id', $group->id)->exists()) {
                                $menu->variantGroups()->attach($group->id);
                            }

                            $this->chatHistory[] = [
                                'sender' => 'ai',
                                'text' => $aiResponse,
                                'time' => now()->format('H:i'),
                            ];
                            $this->dispatch('showToast', message: $aiResponse, type: 'success', title: 'Berhasil');
                        }
                        break;

                    default:
                        $this->chatHistory[] = [
                            'sender' => 'ai',
                            'text' => $aiResponse,
                            'time' => now()->format('H:i'),
                        ];
                        break;
                }
            }

            // Dispatch scroll and reactive component refresh events
            $this->dispatch('chat-message-added');
            $this->dispatch('refreshComponent');

        } catch (\Exception $e) {
            Log::error('AI Command Center Error: '.$e->getMessage());
            $this->chatHistory[] = [
                'sender' => 'ai',
                'text' => 'Maaf, sistem mengalami kesalahan: '.$e->getMessage(),
                'time' => now()->format('H:i'),
            ];
            $this->dispatch('showToast', message: $e->getMessage(), type: 'error', title: 'Gagal');
            $this->dispatch('chat-message-added');
        } finally {
            $this->persistChatHistory();
        }
    }

    private function persistChatHistory(): void
    {
        if (! auth()->check()) {
            return;
        }

        if (! $this->activeChatId) {
            $this->startNewChat(false);
        }

        AiChatHistory::query()
            ->where('user_id', auth()->id())
            ->where('id', $this->activeChatId)
            ->update(['messages' => array_values(array_slice($this->chatHistory, -100))]);

        $this->refreshChatSessions();
    }

    private function greetingMessage(): array
    {
        return [
            'sender' => 'ai',
            'text' => 'Halo! Saya POS AI Assistant. Saya siap membantu Anda mengelola sistem kasir. Silakan ajukan pertanyaan atau instruksikan perubahan!',
            'time' => now()->format('H:i'),
        ];
    }

    private function refreshChatSessions(): void
    {
        if (! auth()->check()) {
            $this->chatSessions = [];

            return;
        }

        $this->chatSessions = AiChatHistory::query()
            ->where('user_id', auth()->id())
            ->latest('updated_at')
            ->limit(20)
            ->get(['id', 'title', 'updated_at'])
            ->map(fn ($history) => [
                'id' => $history->id,
                'title' => $history->title ?: 'Chat baru',
                'updated_at' => optional($history->updated_at)->diffForHumans(),
            ])
            ->toArray();
    }

    private function updateActiveChatTitle(string $userQuery): void
    {
        if (! $this->activeChatId || ! auth()->check()) {
            return;
        }

        $history = AiChatHistory::query()
            ->where('user_id', auth()->id())
            ->find($this->activeChatId);

        if (! $history || ($history->title !== 'Chat baru' && ! str_starts_with($history->title, 'Chat '))) {
            return;
        }

        $title = trim(preg_replace('/\s+/', ' ', $userQuery));
        $title = mb_strlen($title) > 48 ? mb_substr($title, 0, 45).'...' : $title;

        $history->update(['title' => $title ?: 'Chat baru']);
        $this->refreshChatSessions();
    }

    private function resolvePricingTargets(string $priceTier, string $salesChannel, Menu $menu): array
    {
        $priceTier = $this->cleanAiValue($priceTier);
        $salesChannel = $this->cleanAiValue($salesChannel);
        $notes = [];

        $tierMatchedAsChannel = $this->findSalesChannelByName($priceTier);
        if ($tierMatchedAsChannel && $salesChannel === '') {
            $salesChannel = $tierMatchedAsChannel->nama_channel;
            $priceTier = '';
            $notes[] = "'{$tierMatchedAsChannel->nama_channel}' dikenali sebagai sales channel, bukan price tier.";
        }

        $channelMatchedAsTier = $this->findPriceTierByName($salesChannel);
        if ($channelMatchedAsTier && $priceTier === '') {
            $priceTier = $channelMatchedAsTier->nama_tier;
            $salesChannel = '';
            $notes[] = "'{$channelMatchedAsTier->nama_tier}' dikenali sebagai price tier.";
        }

        $tiers = $this->resolvePriceTierSelection($priceTier, $menu, $salesChannel);
        if (empty($tiers)) {
            $availableTiers = PriceTier::pluck('nama_tier')->implode(', ');

            return [
                'error' => "Mau tier mana yang diubah? (Tier yang tersedia: {$availableTiers})",
                'tiers' => [],
                'channels' => [],
                'notes' => $notes,
            ];
        }

        $channels = $this->resolveSalesChannelSelection($salesChannel, $menu, $priceTier);
        if (empty($channels)) {
            $availableChannels = SalesChannel::pluck('nama_channel')->implode(', ');

            return [
                'error' => "Mau sales channel mana yang diubah? (Channel yang tersedia: {$availableChannels})",
                'tiers' => [],
                'channels' => [],
                'notes' => $notes,
            ];
        }

        if ($priceTier === '' && count($tiers) === 1) {
            $notes[] = "Price tier otomatis dipakai: {$tiers[0]->nama_tier}.";
        }

        if ($salesChannel === '' && count($channels) === 1) {
            $notes[] = "Sales channel otomatis dipakai: {$channels[0]->nama_channel}.";
        }

        return [
            'error' => null,
            'tiers' => $tiers,
            'channels' => $channels,
            'notes' => array_values(array_unique($notes)),
        ];
    }

    private function resolvePriceTierSelection(string $priceTier, Menu $menu, string $salesChannel): array
    {
        if ($this->isAllValue($priceTier)) {
            return PriceTier::query()->where('is_active', true)->get()->all();
        }

        if ($priceTier !== '') {
            $tier = $this->findPriceTierByName($priceTier);

            return $tier ? [$tier] : [];
        }

        $activeTiers = PriceTier::query()->where('is_active', true)->get();
        if ($activeTiers->count() === 1) {
            return [$activeTiers->first()];
        }

        if ($salesChannel !== '') {
            $channel = $this->findSalesChannelByName($salesChannel);
            if ($channel) {
                $existingTiers = $menu->menuPrices()
                    ->where('sales_channel_id', $channel->id)
                    ->with('priceTier')
                    ->get()
                    ->pluck('priceTier')
                    ->filter()
                    ->unique('id')
                    ->values();

                if ($existingTiers->count() === 1) {
                    return [$existingTiers->first()];
                }
            }
        }

        return [];
    }

    private function resolveSalesChannelSelection(string $salesChannel, Menu $menu, string $priceTier): array
    {
        if ($this->isAllValue($salesChannel)) {
            return SalesChannel::query()->where('is_active', true)->get()->all();
        }

        if ($salesChannel !== '') {
            $channel = $this->findSalesChannelByName($salesChannel);

            return $channel ? [$channel] : [];
        }

        $activeChannels = SalesChannel::query()->where('is_active', true)->get();
        if ($activeChannels->count() === 1) {
            return [$activeChannels->first()];
        }

        if ($priceTier !== '') {
            $tier = $this->findPriceTierByName($priceTier);
            if ($tier) {
                $existingChannels = $menu->menuPrices()
                    ->where('price_tier_id', $tier->id)
                    ->with('salesChannel')
                    ->get()
                    ->pluck('salesChannel')
                    ->filter()
                    ->unique('id')
                    ->values();

                if ($existingChannels->count() === 1) {
                    return [$existingChannels->first()];
                }
            }
        }

        return [];
    }

    private function findPriceTierByName(string $name): ?PriceTier
    {
        return $this->findByNormalizedName(PriceTier::query()->where('is_active', true)->get(), 'nama_tier', $name);
    }

    private function findSalesChannelByName(string $name): ?SalesChannel
    {
        return $this->findByNormalizedName(SalesChannel::query()->where('is_active', true)->get(), 'nama_channel', $name);
    }

    private function findByNormalizedName($items, string $attribute, string $name)
    {
        $needle = $this->normalizeLookup($name);
        if ($needle === '') {
            return null;
        }

        return $items->first(function ($item) use ($attribute, $needle) {
            $candidate = $this->normalizeLookup((string) $item->{$attribute});

            return $candidate === $needle
                || str_contains($candidate, $needle)
                || str_contains($needle, $candidate);
        });
    }

    private function cleanAiValue(mixed $value): string
    {
        $value = trim((string) $value);

        return in_array(mb_strtolower($value), ['', 'none', 'null', '-'], true) ? '' : $value;
    }

    private function normalizeLookup(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', mb_strtolower($value));
    }

    private function isAllValue(string $value): bool
    {
        return in_array($this->normalizeLookup($value), ['all', 'semua', 'seluruh'], true);
    }

    private function isDefaultDineInChannel(SalesChannel $channel): bool
    {
        return $this->normalizeLookup($channel->nama_channel) === 'dinein';
    }

    private function isDefaultBasePriceTarget(PriceTier $tier, SalesChannel $channel): bool
    {
        return $this->normalizeLookup($tier->nama_tier) === 'reguler'
            && $this->isDefaultDineInChannel($channel);
    }

    private function pricingSuccessMessage(Menu $menu, float $priceValue, array $updatedRows, array $notes): string
    {
        $targetList = collect($updatedRows)
            ->map(fn ($row) => "- Tier: {$row['tier']}, Channel: {$row['channel']}")
            ->implode("\n");

        $noteText = empty($notes) ? '' : "\nCatatan: ".implode(' ', $notes);

        return "Status: Berhasil\n"
            .'Aksi: Harga menu '.$menu->nama_menu.' berhasil diperbarui menjadi Rp'.number_format($priceValue, 0, ',', '.')." dan sudah diverifikasi di database.\n"
            ."Target:\n{$targetList}"
            .$noteText."\n"
            .'Link: /menu/'.$menu->id.'/edit';
    }

    private function resolveReportType(array &$payload, string $userQuery): string
    {
        $reportType = strtolower(trim((string) ($payload['report_type'] ?? 'none')));

        if ($reportType !== '' && $reportType !== 'none') {
            return $reportType;
        }

        $query = mb_strtolower($userQuery);

        if (str_contains($query, 'laris') || str_contains($query, 'terlaris') || str_contains($query, 'paling laku')) {
            return 'top_selling_menus';
        }

        if (str_contains($query, 'sedikit') || str_contains($query, 'kurang laris') || str_contains($query, 'tidak laris') || str_contains($query, 'tersepi') || str_contains($query, 'paling sepi')) {
            return 'least_selling_menus';
        }

        if (str_contains($query, 'komposisi') || str_contains($query, 'resep') || str_contains($query, 'bahan baku menu') || str_contains($query, 'bahan menu')) {
            return 'menu_ingredients';
        }

        if (str_contains($query, 'omzet') || str_contains($query, 'total penjualan') || str_contains($query, 'ringkasan penjualan')) {
            return 'sales_summary';
        }

        if (str_contains($query, 'penjualan') || str_contains($query, 'terjual') || str_contains($query, 'berapa laku')) {
            if (empty($payload['menu_name'])) {
                $menu = \App\Models\Menu::query()->get(['nama_menu'])
                    ->first(fn ($menu) => str_contains($query, mb_strtolower($menu->nama_menu)));

                if ($menu) {
                    $payload['menu_name'] = $menu->nama_menu;
                }
            }

            if (! empty($payload['menu_name'])) {
                return 'menu_sales';
            }
        }

        return 'none';
    }

    public function render()
    {
        // Get all menu prices with pagination for live preview
        $prices = MenuPrice::with(['menu', 'priceTier', 'salesChannel'])
            ->when(! empty($this->search), function ($query) {
                $query->whereHas('menu', function ($q) {
                    $q->where('nama_menu', 'LIKE', '%'.$this->search.'%');
                })
                    ->orWhereHas('priceTier', function ($q) {
                        $q->where('nama_tier', 'LIKE', '%'.$this->search.'%');
                    })
                    ->orWhereHas('salesChannel', function ($q) {
                        $q->where('nama_channel', 'LIKE', '%'.$this->search.'%');
                    });
            })
            ->orderBy('updated_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.admin.ai-command-center', [
            'prices' => $prices,
            'title' => 'AI Command Center Autopilot',
        ])->layout('layouts.app', ['title' => 'AI Command Center']);
    }
}
