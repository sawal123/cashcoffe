<?php

namespace App\Livewire\Order\Traits;

use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\Discount;
use App\Models\Ingredients;
use App\Models\RiwayatStock;
use App\Models\MenuIngredients;
use App\Models\PaymentMethod;
use App\Models\SalesChannel;
use App\Models\PriceTier;
use App\Models\VariantOption;
use App\Models\VariantPrice;
use App\Models\MenuPrice;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

trait HandlesOrderSubmit
{
    public function editOrder($id)
    {
        $this->orderId = $id;

        $pesanan = Pesanan::with(['items.menu', 'items.variants', 'discount'])->findOrFail($id);
        $this->mejas_id = $pesanan->mejas_id;
        $this->metode_pembayaran = $pesanan->payment_method_id;
        $this->sales_channel_id = $pesanan->sales_channel_id;
        $this->status = $pesanan->status;

        $this->discountId = $pesanan->discount_id;
        $this->nama_costumer = $pesanan->nama;
        $this->uang_tunai = $pesanan->uang_tunai;
        $this->kembalian = $pesanan->kembalian;
        $this->discount = $pesanan->discount->kode_diskon ?? null;
        $this->member = $pesanan->member->phone ?? null;

        // Jika ada kode diskon dari DB, otomatis anggap sudah diverifikasi
        if ($this->discount) {
            $this->isDiscountVerified = true;
        }

        $this->pesanan = $pesanan->items->mapWithKeys(function ($item) {
            $optionIds = $item->variants->pluck('id')->toArray();
            sort($optionIds);
            
            // Generate Key yang konsisten dengan sistem cartKey di HandlesCartInput
            $optionSlug = count($optionIds) > 0 ? '_' . md5(json_encode($optionIds)) : '';
            $cartKey = $item->menus_id . $optionSlug;

            return [
                $cartKey => [
                    'id' => $item->menus_id,
                    'nama_menu' => $item->menu->nama_menu ?? '',
                    'harga' => (int) $item->harga_satuan,
                    'gambar' => $item->menu->gambar ?? '',
                    'qty' => $item->qty,
                    'catatan' => $item->catatan_item,
                    'status' => $item->status,
                    'selected_options' => $optionIds,
                    'display_options' => $item->variants->pluck('nama_opsi')->toArray(),
                ],
            ];
        })->toArray();
    }

    public function batalkanPesanan($id)
    {
        $targetId = is_numeric($id) ? $id : base64_decode($id);

        try {
            DB::transaction(function () use ($targetId) {
                $pesanan = Pesanan::where('id', $targetId)->lockForUpdate()->with(['items', 'discount'])->firstOrFail();

                if ($pesanan->status === 'dibatalkan') {
                    $this->dispatch('showToast', message: 'Pesanan sudah dibatalkan sebelumnya.', type: 'info', title: 'Info');
                    return;
                }

                if ($pesanan->status === 'selesai') {
                    $this->dispatch('showToast', message: 'Pesanan yang sudah selesai tidak dapat dibatalkan.', type: 'error', title: 'Error');
                    return;
                }

                if ($pesanan->status !== 'diproses') {
                    $this->dispatch('showToast', message: 'Status pesanan tidak valid untuk dibatalkan.', type: 'error', title: 'Error');
                    return;
                }

                $pesanan->decrementDiscountUsageOnCancellation();

                $pesanan->update([
                    'status' => 'dibatalkan',
                ]);

                $this->dispatch('close-modal', name: 'confirm-cancel-modal');
                $this->dispatch('showToast', message: 'Pesanan berhasil dibatalkan.', type: 'success', title: 'Success');
            });
        } catch (\Exception $e) {
            $this->dispatch('showToast', message: 'Gagal membatalkan pesanan: ' . $e->getMessage(), type: 'error', title: 'Error');
        }
    }

    private function validateAndCalculateServerItems(int $salesChannelId, int $priceTierId): array
    {
        $itemsToProcess = [];

        foreach ($this->pesanan as $p) {
            // Validate Qty: integer, >= 1
            $rawQty = $p['qty'] ?? null;
            if (is_null($rawQty) || !is_numeric($rawQty) || filter_var($rawQty, FILTER_VALIDATE_INT) === false) {
                throw new \InvalidArgumentException('Jumlah (qty) pesanan harus berupa integer!');
            }
            $qty = (int) $rawQty;
            if ($qty < 1 || $qty > 10000) {
                throw new \InvalidArgumentException('Jumlah (qty) pesanan tidak valid!');
            }

            // Fetch Menu from DB
            $menuId = $p['id'] ?? null;
            $menu = $menuId ? Menu::find($menuId) : null;
            if (!$menu) {
                throw new \InvalidArgumentException('Menu tidak ditemukan di database!');
            }
            if (isset($menu->is_active) && !$menu->is_active) {
                throw new \InvalidArgumentException('Menu ' . $menu->nama_menu . ' tidak aktif!');
            }

            // Verify Variants & Calculate Extra Price from DB
            $rawSelectedOptions = $p['selected_options'] ?? [];
            if (!is_array($rawSelectedOptions)) {
                throw new \InvalidArgumentException('Format opsi varian tidak valid!');
            }

            // Deduplicate options to prevent double counting or rule bypass
            $selectedOptionIds = array_values(array_unique($rawSelectedOptions));

            $variantGroups = $menu->variantGroups()->with('options')->get();

            $optionToGroupMap = [];
            $groupOptionCount = [];
            foreach ($variantGroups as $vg) {
                $groupOptionCount[$vg->id] = 0;
                foreach ($vg->options as $opt) {
                    $optionToGroupMap[$opt->id] = $vg->id;
                }
            }

            $extraPrice = 0;
            foreach ($selectedOptionIds as $optId) {
                if (!isset($optionToGroupMap[$optId])) {
                    $optModel = VariantOption::find($optId);
                    if (!$optModel) {
                        throw new \InvalidArgumentException('Varian ID ' . $optId . ' tidak valid!');
                    }
                    throw new \InvalidArgumentException('Varian ' . $optModel->nama_opsi . ' tidak terkait dengan menu ' . $menu->nama_menu);
                }

                $groupId = $optionToGroupMap[$optId];
                $groupOptionCount[$groupId]++;

                $vPrice = VariantPrice::where('variant_option_id', $optId)
                    ->where('price_tier_id', $priceTierId)
                    ->where('sales_channel_id', $salesChannelId)
                    ->first();

                if ($vPrice) {
                    $extraPrice += (int) $vPrice->extra_price;
                } else {
                    $optModel = VariantOption::find($optId);
                    $extraPrice += (int) ($optModel->extra_price ?? 0);
                }
            }

            // Validate VariantGroup rules (is_required & selection_type)
            foreach ($variantGroups as $vg) {
                $count = $groupOptionCount[$vg->id];

                if ($vg->is_required && $count === 0) {
                    throw new \InvalidArgumentException('Varian ' . $vg->nama_group . ' wajib dipilih!');
                }

                if ($vg->selection_type === 'single' && $count > 1) {
                    throw new \InvalidArgumentException('Varian ' . $vg->nama_group . ' hanya boleh dipilih maksimal 1 opsi!');
                }
            }

            // Base price recalculation from DB
            $tieredPrice = MenuPrice::where('menu_id', $menu->id)
                ->where('price_tier_id', $priceTierId)
                ->where('sales_channel_id', $salesChannelId)
                ->first();

            if ($tieredPrice) {
                $hargaBase = ($tieredPrice->h_promo > 0) ? $tieredPrice->h_promo : $tieredPrice->harga;
            } else {
                $hargaBase = ($menu->h_promo > 0) ? $menu->h_promo : $menu->harga;
            }

            $hargaJual = (int) ($hargaBase + $extraPrice);
            $subtotalItem = $hargaJual * $qty;
            $profitPerItem = ($hargaJual - $menu->h_pokok) * $qty;

            $itemsToProcess[] = [
                'menu' => $menu,
                'qty' => $qty,
                'harga_jual' => $hargaJual,
                'subtotal' => $subtotalItem,
                'profit' => $profitPerItem,
                'selected_options' => $selectedOptionIds,
                'catatan' => $p['catatan'] ?? null,
            ];
        }

        return $itemsToProcess;
    }

    public function updateOrder()
    {
        if (!$this->orderId) return;

        if (empty($this->pesanan) || !is_array($this->pesanan)) {
            $this->dispatch('showToast', message: 'Pesanan masih kosong', type: 'error', title: 'Error');
            return;
        }

        if (! $this->nama_costumer) {
            $this->dispatch('showToast', message: 'Nama costumer tidak boleh kosong!', type: 'error', title: 'Error');
            return;
        }

        if (! $this->metode_pembayaran) {
            $this->dispatch('showToast', message: 'Metode pembayaran wajib dipilih!', type: 'error', title: 'Error');
            return;
        }

        try {
            DB::transaction(function () {
                $pesanan = Pesanan::where('id', $this->orderId)->lockForUpdate()->with(['items', 'discount'])->firstOrFail();

                if ($pesanan->status !== 'diproses') {
                    throw new \InvalidArgumentException('Pesanan dengan status ' . $pesanan->status . ' tidak dapat diubah.');
                }

                // Capture old applied global discount before any changes
                $oldDiscountId = null;
                $oldDiscountModel = null;
                if ($pesanan->discount_id && $pesanan->discount && $pesanan->discount->scope === 'global' && $pesanan->discount_value > 0) {
                    $oldDiscountId = $pesanan->discount_id;
                    $oldDiscountModel = $pesanan->discount;
                }

                // 1. Verify Payment Method
                $pm = PaymentMethod::find($this->metode_pembayaran);
                if (!$pm || (isset($pm->is_active) && !$pm->is_active)) {
                    throw new \InvalidArgumentException('Metode pembayaran tidak valid atau tidak aktif!');
                }

                // 2. Verify Sales Channel
                $salesChannelId = $this->sales_channel_id ?? 1;
                $salesChannel = SalesChannel::find($salesChannelId);
                if (!$salesChannel || (isset($salesChannel->is_active) && !$salesChannel->is_active)) {
                    throw new \InvalidArgumentException('Sales channel tidak valid atau tidak aktif!');
                }

                // 3. Verify Price Tier
                $user = Auth::user();
                $priceTierId = $user?->branch ? $user->branch->price_tier_id : (PriceTier::first()?->id ?? 1);
                $priceTier = PriceTier::find($priceTierId);
                if (!$priceTier || (isset($priceTier->is_active) && !$priceTier->is_active)) {
                    throw new \InvalidArgumentException('Price tier tidak valid atau tidak aktif!');
                }

                // 4. Validate and calculate items server side
                $itemsToProcess = $this->validateAndCalculateServerItems($salesChannelId, $priceTierId);

                $pesanan->items()->delete();

                $total = 0;
                $totalProfit = 0;
                $discountAmount = 0;
                $memberId = $this->memberIdFromPhone($this->member);

                $disc = null;
                if ($this->discount_id) {
                    $disc = Discount::with('discountItems')->find($this->discount_id);
                    if ($disc && ! $disc->canBeUsedByMemberId($memberId)) {
                        $disc = null;
                        $this->discount_id = null;
                    }
                }

                foreach ($itemsToProcess as $item) {
                    $menu = $item['menu'];
                    $qty = $item['qty'];
                    $hargaJual = $item['harga_jual'];
                    $subtotalItem = $item['subtotal'];
                    $profitPerItem = $item['profit'];
                    $selectedOptionIds = $item['selected_options'];

                    $itemDiscountValue = 0;

                    if ($disc && $disc->is_active && $disc->scope !== 'global') {
                        $isEligible = false;
                        foreach ($disc->discountItems as $di) {
                            if ($di->model_type === 'App\Models\Menu' && $di->model_id == $menu->id) {
                                $isEligible = true; break;
                            }
                            if ($di->model_type === 'App\Models\Category' && $di->model_id == $menu->categories_id) {
                                $isEligible = true; break;
                            }
                        }
                        if ($isEligible) {
                            if ($disc->jenis_diskon === 'persentase') {
                                $itemDiscountValue = round($subtotalItem * ($disc->nilai_diskon / 100));
                                if ($disc->maksimum_diskon && $itemDiscountValue > $disc->maksimum_diskon) {
                                    $itemDiscountValue = $disc->maksimum_diskon;
                                }
                            } elseif ($disc->jenis_diskon === 'nominal') {
                                $itemDiscountValue = $disc->nilai_diskon * $qty; 
                            }
                        }
                    }

                    $newItem = $pesanan->items()->create([
                        'menus_id' => $menu->id,
                        'qty' => $qty,
                        'harga_satuan' => $hargaJual,
                        'subtotal' => $subtotalItem - $itemDiscountValue,
                        'discount_value' => $itemDiscountValue,
                        'profit' => $profitPerItem - $itemDiscountValue,
                        'catatan_item' => $item['catatan'],
                    ]);

                    if (!empty($selectedOptionIds)) {
                        $newItem->variants()->sync($selectedOptionIds);
                    }

                    $total += $subtotalItem - $itemDiscountValue;
                    $totalProfit += $profitPerItem - $itemDiscountValue;
                }

                // Determine new applied global discount
                $newDiscountId = null;
                if ($disc && $disc->is_active && $disc->scope === 'global') {
                    if (
                        (!$disc->tanggal_mulai || $disc->tanggal_mulai <= now()) &&
                        (!$disc->tanggal_akhir || $disc->tanggal_akhir >= now())
                    ) {
                        // Existing order using same discount may bypass limit check:
                        // the slot is already "occupied" by this order's previous usage.
                        $isSameExistingDiscount = ($disc->id === $oldDiscountId);
                        $limitBlocking = !$isSameExistingDiscount
                            && !is_null($disc->limit)
                            && !is_null($disc->digunakan)
                            && $disc->digunakan >= $disc->limit;

                        if ($limitBlocking) {
                            // limit habis & bukan discount yang sama → tolak
                        } elseif ($disc->minimum_transaksi && $total < $disc->minimum_transaksi) {
                            // tidak memenuhi minimum transaksi
                        } else {
                            if ($disc->jenis_diskon === 'persentase') {
                                $discountAmount = round($total * ($disc->nilai_diskon / 100));
                                if ($disc->maksimum_diskon && $discountAmount > $disc->maksimum_diskon) {
                                    $discountAmount = $disc->maksimum_diskon;
                                }
                            } elseif ($disc->jenis_diskon === 'nominal') {
                                $discountAmount = $disc->nilai_diskon;
                            }
                            if ($discountAmount > 0) {
                                $newDiscountId = $disc->id;
                            }
                        }
                    }
                }

                // Discount usage accounting: diff old vs new
                if ($oldDiscountId !== $newDiscountId) {
                    // Old applied global discount no longer active
                    if ($oldDiscountId !== null && $oldDiscountModel && $oldDiscountModel->digunakan > 0) {
                        $oldDiscountModel->decrement('digunakan');
                    }
                    // New applied global discount (fresh increment) — only when genuinely new
                    if ($newDiscountId !== null && $newDiscountId !== $oldDiscountId) {
                        $disc->increment('digunakan');
                    }
                }
                // If same discount remains applied: no change to counter

                // For global disc: only persist to order if it was actually applied
                // For non-global (item) disc: $disc is the applied disc regardless
                if ($disc && $disc->scope === 'global') {
                    $appliedDiscountId = $newDiscountId;
                } else {
                    $appliedDiscountId = $disc?->id;
                }

                $totalAfterDiscount = max(0, $total - $discountAmount);

                $pesanan->update([
                    'mejas_id' => $this->mejas_id,
                    'nama' => $this->nama_costumer,
                    'member_id' => $memberId,
                    'payment_method_id' => $this->metode_pembayaran ?: null,
                    'discount_id' => $appliedDiscountId,
                    'discount_value' => $discountAmount,
                    'sales_channel_id' => $salesChannelId,
                    'total' => $total,
                    'total_profit' => $totalProfit,
                    'uang_tunai' => $this->isCash ? $this->uang_tunai : 0,
                    'kembalian' => $this->isCash ? $this->uang_tunai - $totalAfterDiscount : 0,
                ]);
            });

            $this->dispatch('showToast', type: 'success', message: 'Pesanan berhasil diperbarui');
        } catch (\Exception $e) {
            $this->dispatch('showToast', type: 'error', message: 'Gagal update pesanan: ' . $e->getMessage());
        }
    }

    public function saveOrder()
    {
        if (empty($this->pesanan) || !is_array($this->pesanan)) {
            $this->dispatch('showToast', message: 'Pesanan masih kosong', type: 'error', title: 'Error');
            return;
        }

        if (! $this->nama_costumer) {
            $this->dispatch('showToast', message: 'Nama costumer tidak boleh kosong!', type: 'error', title: 'Error');
            return;
        }

        if (! $this->metode_pembayaran) {
            $this->dispatch('showToast', message: 'Metode pembayaran wajib dipilih!', type: 'error', title: 'Error');
            return;
        }

        try {
            $pesanan = DB::transaction(function () {
                // 1. Verify Payment Method
                $pm = PaymentMethod::find($this->metode_pembayaran);
                if (!$pm || (isset($pm->is_active) && !$pm->is_active)) {
                    throw new \InvalidArgumentException('Metode pembayaran tidak valid atau tidak aktif!');
                }

                // 2. Verify Sales Channel
                $salesChannelId = $this->sales_channel_id ?? 1;
                $salesChannel = SalesChannel::find($salesChannelId);
                if (!$salesChannel || (isset($salesChannel->is_active) && !$salesChannel->is_active)) {
                    throw new \InvalidArgumentException('Sales channel tidak valid atau tidak aktif!');
                }

                // 3. Verify Price Tier
                $user = Auth::user();
                $priceTierId = $user?->branch ? $user->branch->price_tier_id : (PriceTier::first()?->id ?? 1);
                $priceTier = PriceTier::find($priceTierId);
                if (!$priceTier || (isset($priceTier->is_active) && !$priceTier->is_active)) {
                    throw new \InvalidArgumentException('Price tier tidak valid atau tidak aktif!');
                }

                // 4. Validate and calculate items server side
                $itemsToProcess = $this->validateAndCalculateServerItems($salesChannelId, $priceTierId);

                $randomString = strtoupper(Str::random(8));
                $tanggal = date('dm');
                $kodeFinal = $randomString . $tanggal;
                $memberId = $this->memberIdFromPhone($this->member);

                $pesanan = Pesanan::create([
                    'kode' => $kodeFinal,
                    'mejas_id' => $this->mejas_id,
                    'nama' => $this->nama_costumer,
                    'user_id' => Auth::id(),
                    'member_id' => $memberId,
                    'discount_id' => null,
                    'discount_value' => 0,
                    'payment_method_id' => $this->metode_pembayaran ?: null,
                    'sales_channel_id' => $salesChannelId,
                    'total' => 0,
                    'total_profit' => 0,
                    'catatan' => null,
                ]);

                $total = 0;
                $totalProfit = 0;
                $discountAmount = 0;

                $disc = null;
                if ($this->discountId) {
                    $disc = Discount::with('discountItems')->find($this->discountId);
                    if ($disc && ! $disc->canBeUsedByMemberId($memberId)) {
                        $disc = null;
                        $this->discountId = null;
                    }
                }

                foreach ($itemsToProcess as $item) {
                    $menu = $item['menu'];
                    $qty = $item['qty'];
                    $hargaJual = $item['harga_jual'];
                    $subtotalItem = $item['subtotal'];
                    $profitPerItem = $item['profit'];
                    $selectedOptionIds = $item['selected_options'];

                    $itemDiscountValue = 0;

                    if ($disc && $disc->is_active && $disc->scope !== 'global') {
                        $isEligible = false;
                        foreach ($disc->discountItems as $di) {
                            if ($di->model_type === 'App\Models\Menu' && $di->model_id == $menu->id) {
                                $isEligible = true; break;
                            }
                            if ($di->model_type === 'App\Models\Category' && $di->model_id == $menu->categories_id) {
                                $isEligible = true; break;
                            }
                        }
                        if ($isEligible) {
                            if ($disc->jenis_diskon === 'persentase') {
                                $itemDiscountValue = round($subtotalItem * ($disc->nilai_diskon / 100));
                                if ($disc->maksimum_diskon && $itemDiscountValue > $disc->maksimum_diskon) {
                                    $itemDiscountValue = $disc->maksimum_diskon;
                                }
                            } elseif ($disc->jenis_diskon === 'nominal') {
                                $itemDiscountValue = $disc->nilai_diskon * $qty; 
                            }
                        }
                    }

                    $newItem = $pesanan->items()->create([
                        'menus_id' => $menu->id,
                        'qty' => $qty,
                        'harga_satuan' => $hargaJual,
                        'subtotal' => $subtotalItem - $itemDiscountValue,
                        'discount_value' => $itemDiscountValue,
                        'profit' => $profitPerItem - $itemDiscountValue,
                        'catatan_item' => $item['catatan'],
                    ]);

                    if (!empty($selectedOptionIds)) {
                        $newItem->variants()->sync($selectedOptionIds);
                    }

                    $total += $subtotalItem - $itemDiscountValue;
                    $totalProfit += $profitPerItem - $itemDiscountValue;
                }

                if ($disc && $disc->is_active && $disc->scope === 'global') {
                    if (
                        (! $disc->tanggal_mulai || $disc->tanggal_mulai <= now()) &&
                        (! $disc->tanggal_akhir || $disc->tanggal_akhir >= now())
                    ) {
                        if (! is_null($disc->limit) && ! is_null($disc->digunakan) && $disc->digunakan >= $disc->limit) {
                            // Limit habis
                        } elseif ($disc->minimum_transaksi && $total < $disc->minimum_transaksi) {
                            // Tidak memenuhi minimal transaksi
                        } else {
                            if ($disc->jenis_diskon === 'persentase') {
                                $discountAmount = round($total * ($disc->nilai_diskon / 100));
                                if ($disc->maksimum_diskon && $discountAmount > $disc->maksimum_diskon) {
                                    $discountAmount = $disc->maksimum_diskon;
                                }
                            } elseif ($disc->jenis_diskon === 'nominal') {
                                $discountAmount = $disc->nilai_diskon;
                            }
                            $disc->increment('digunakan');
                        }
                    }
                }

                $totalAfterDiscount = max(0, $total - $discountAmount);

                $pesanan->update([
                    'discount_id' => $disc?->id,
                    'nama' => $this->nama_costumer,
                    'discount_value' => $discountAmount,
                    'sales_channel_id' => $salesChannelId,
                    'total' => $total,
                    'total_profit' => $totalProfit,
                    'uang_tunai' => $this->isCash ? $this->uang_tunai : 0,
                    'kembalian' => $this->isCash ? $this->uang_tunai - $totalAfterDiscount : 0,
                ]);

                return $pesanan;
            });

            $this->pesanan = [];
            $this->mejas_id = null;
            $this->nama_costumer = '';

            $this->setLastOrderSnapshot($pesanan->fresh([
                'items.menu',
                'items.variants',
                'paymentMethod',
                'salesChannel',
            ]));

            $this->dispatch('showToast', message: 'Pesanan berhasil disimpan.', type: 'success', title: 'Success');
            if ($this->metode_pembayaran) {
                $this->dispatch('open-modal', name: 'order-success');
            }
        } catch (\Exception $e) {
            $this->dispatch('showToast', type: 'error', message: 'Gagal simpan pesanan: ' . $e->getMessage());
        }
    }

    public function completeLastOrder()
    {
        $this->finishLastOrder();
    }

    public function completeLastOrderAndPrint()
    {
        $pesanan = $this->finishLastOrder();

        if (! $pesanan) {
            return null;
        }

        return route('struk.print', base64_encode($pesanan->id));
    }

    private function finishLastOrder()
    {
        if (! $this->lastPesananId) {
            $this->dispatch('showToast', message: 'Pesanan belum tersedia.', type: 'error', title: 'Error');
            return null;
        }

        DB::beginTransaction();
        try {
            $pesanan = Pesanan::where('id', $this->lastPesananId)->lockForUpdate()->with([
                'items.variants',
                'items.menu',
                'discount',
                'paymentMethod',
                'salesChannel',
            ])->firstOrFail();

            if (! $pesanan->payment_method_id) {
                $this->dispatch('showToast', message: 'Metode pembayaran harus dipilih!', type: 'info', title: 'Info');
                DB::rollBack();
                return null;
            }

            if ($pesanan->status === 'selesai') {
                $this->setLastOrderSnapshot($pesanan);
                $this->dispatch('showToast', message: 'Pesanan sudah selesai sebelumnya.', type: 'info', title: 'Info');
                DB::rollBack();
                return $pesanan;
            }

            if ($pesanan->status === 'dibatalkan') {
                $this->dispatch('showToast', message: 'Pesanan yang sudah dibatalkan tidak dapat diselesaikan.', type: 'error', title: 'Error');
                DB::rollBack();
                return null;
            }

            if ($pesanan->status !== 'diproses') {
                $this->dispatch('showToast', message: 'Status pesanan tidak valid untuk diselesaikan.', type: 'error', title: 'Error');
                DB::rollBack();
                return null;
            }

            $pesanan->update(['status' => 'selesai']);

            if ($pesanan->member_id) {
                $totalAfterDiscount = max(0, $pesanan->total - $pesanan->discount_value);
                $earnedPoints = floor($totalAfterDiscount / 10000);
                $member = \App\Models\Member::find($pesanan->member_id);

                if ($member) {
                    $member->increment('points', $earnedPoints);
                    $member->increment('total_pengeluaran', $totalAfterDiscount);
                }
            }

            $this->reduceStock($pesanan);

            DB::commit();

            $pesanan = $pesanan->fresh([
                'items.menu',
                'items.variants',
                'paymentMethod',
                'salesChannel',
            ]);

            $this->setLastOrderSnapshot($pesanan);

            $this->dispatch('showToast', message: 'Pesanan selesai.', type: 'success', title: 'Success');

            return $pesanan;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showToast', type: 'error', message: 'Gagal menyelesaikan pesanan: ' . $e->getMessage());

            return null;
        }
    }

    private function setLastOrderSnapshot(Pesanan $pesanan)
    {
        $this->lastPesananId = $pesanan->id;
        $this->lastKodePesanan = $pesanan->kode;
        $this->lastNamaCostumer = $pesanan->nama;
        $this->lastTotalPesanan = $pesanan->total;
        $this->lastDiscountPesanan = $pesanan->discount_value ?? 0;
        $this->lastFinalTotalPesanan = max(0, $pesanan->total - ($pesanan->discount_value ?? 0));
        $this->lastPaymentMethodName = $pesanan->paymentMethod->nama_metode ?? '-';
        $this->lastSalesChannelName = $pesanan->salesChannel->nama_channel ?? 'Dine In';
        $this->lastCreatedAt = optional($pesanan->created_at)->format('d M Y H:i');
        $this->lastStatusPesanan = $pesanan->status;
        $this->lastOrderItems = $pesanan->items->map(function ($item) {
            return [
                'name' => $item->menu->nama_menu ?? 'Menu Dihapus',
                'qty' => $item->qty,
                'price' => $item->harga_satuan,
                'subtotal' => $item->subtotal ?? ($item->harga_satuan * $item->qty),
                'variants' => $item->variants->pluck('nama_opsi')->filter()->values()->all(),
            ];
        })->values()->all();
    }
    

    private function reduceStock(Pesanan $pesanan)
    {
        $stockChanges = [];

        foreach ($pesanan->items as $item) {
            // 1. Kumpulkan dari resep DASAR menu
            $komposisi = MenuIngredients::where('menu_id', $item->menus_id)->get();
            foreach ($komposisi as $k) {
                if (!isset($stockChanges[$k->ingredient_id])) {
                    $stockChanges[$k->ingredient_id] = 0;
                }
                $stockChanges[$k->ingredient_id] += ($k->qty * $item->qty);
            }

            // 2. Kumpulkan dari resep VARIAN
            $selectedVariants = $item->variants()->with('ingredients')->get();
            foreach ($selectedVariants as $variant) {
                foreach ($variant->ingredients as $vIngredient) {
                    if (!isset($stockChanges[$vIngredient->id])) {
                        $stockChanges[$vIngredient->id] = 0;
                    }
                    $stockChanges[$vIngredient->id] += ($vIngredient->pivot->qty * $item->qty);
                }
            }
        }

        // 3. Eksekusi pemotongan stok (Agregat)
        foreach ($stockChanges as $ingredientId => $totalQty) {
            $ingredient = Ingredients::find($ingredientId);
            if (!$ingredient) continue;

            $before = $ingredient->stok;
            $after = $before - $totalQty;

            $ingredient->update(['stok' => $after]);

            RiwayatStock::create([
                'ingredient_id' => $ingredient->id,
                'kode'          => strtoupper('OUT-' . Str::random(6)),
                'qty'           => $totalQty,
                'qty_before'    => $before,
                'qty_after'     => $after,
                'tipe'          => 'out',
                'keterangan'    => 'Akumulasi resep: pesanan ' . $pesanan->kode,
            ]);
        }
    }

    private function restoreStock(Pesanan $pesanan)
    {
        $stockChanges = [];

        foreach ($pesanan->items as $item) {
            // 1. Kumpulkan dari resep DASAR menu
            $komposisi = MenuIngredients::where('menu_id', $item->menus_id)->get();
            foreach ($komposisi as $k) {
                if (!isset($stockChanges[$k->ingredient_id])) {
                    $stockChanges[$k->ingredient_id] = 0;
                }
                $stockChanges[$k->ingredient_id] += ($k->qty * $item->qty);
            }

            // 2. Kumpulkan dari resep VARIAN
            $selectedVariants = $item->variants()->with('ingredients')->get();
            foreach ($selectedVariants as $variant) {
                foreach ($variant->ingredients as $vIngredient) {
                    if (!isset($stockChanges[$vIngredient->id])) {
                        $stockChanges[$vIngredient->id] = 0;
                    }
                    $stockChanges[$vIngredient->id] += ($vIngredient->pivot->qty * $item->qty);
                }
            }
        }

        // 3. Eksekusi pengembalian stok (Agregat)
        foreach ($stockChanges as $ingredientId => $totalQty) {
            $ingredient = Ingredients::find($ingredientId);
            if (!$ingredient) continue;

            $before = $ingredient->stok;
            $after = $before + $totalQty;

            $ingredient->update(['stok' => $after]);

            RiwayatStock::create([
                'ingredient_id' => $ingredient->id,
                'kode'          => strtoupper('IN-' . Str::random(6)),
                'qty'           => $totalQty,
                'qty_before'    => $before,
                'qty_after'     => $after,
                'tipe'          => 'in',
                'keterangan'    => 'Pengembalian akumulasi: batal pesanan ' . $pesanan->kode,
            ]);
        }
    }
}
