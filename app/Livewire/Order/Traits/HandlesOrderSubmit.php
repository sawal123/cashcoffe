<?php

namespace App\Livewire\Order\Traits;

use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\Discount;
use App\Models\Ingredients;
use App\Models\RiwayatStock;
use App\Models\MenuIngredients;
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
        $this->metode_pembayaran = $pesanan->metode_pembayaran;
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
        // 1. Pastikan me-load relasi 'discount' agar tidak error saat decrement
        // Catatan: Jika parameter $id dari view di-encode, gunakan base64_decode($id)
        $pesanan = Pesanan::with(['items', 'discount'])->findOrFail($id);

        if ($pesanan->status === 'dibatalkan') {
            $this->dispatch('showToast', message: 'Pesanan sudah dibatalkan sebelumnya.', type: 'info', title: 'Info');
            return;
        }

        $statusSebelumnya = $pesanan->status;

        // 2. KEMBALIKAN STOK HANYA JIKA SUDAH DIPOTONG
        // Berdasarkan kode saji() Anda sebelumnya, stok HANYA dipotong saat status 'selesai'.
        // Jadi jika pesanan masih 'diproses' dan dibatalkan, kita tidak boleh menambah stok (karena belum dipotong).
        if ($statusSebelumnya === 'selesai') {
            $this->restoreStock($pesanan);

            // DEDUCT POINTS if member exists
            if ($pesanan->member_id) {
                $totalAfterDiscount = max(0, $pesanan->total - $pesanan->discount_value);
                $earnedPoints = floor($totalAfterDiscount / 10000); // 1 point per 10k
                $member = \App\Models\Member::find($pesanan->member_id);
                if ($member) {
                    $member->decrement('points', $earnedPoints);
                    $member->decrement('total_pengeluaran', $totalAfterDiscount);
                }
            }
        }

        // 3. Kurangi penggunaan diskon jika pesanan menggunakan diskon
        if ($pesanan->discount_id && $pesanan->discount) {
            $pesanan->discount->decrement('digunakan');
        }

        // 4. Update status (dan reset nilai diskon jika memang Anda ingin mengosongkannya di struk)
        $pesanan->update([
            'status' => 'dibatalkan',
            'discount_id' => null,
            'discount_value' => 0,
            // Opsional: Anda mungkin perlu mengupdate total harga juga jika discount_value di-nol-kan
            // 'total' => $pesanan->total + $pesanan->discount_value
        ]);
        $this->dispatch('close-modal', name: 'confirm-cancel-modal');

        $this->dispatch('showToast', message: 'Pesanan berhasil dibatalkan.', type: 'success', title: 'Success');
    }

    public function updateOrder()
    {
        if (!$this->orderId) return;

        DB::beginTransaction();
        try {
            $pesanan = Pesanan::with('items')->findOrFail($this->orderId);

            if ($pesanan->status === 'selesai') {
                $this->dispatch('showToast', message: 'Pesanan sudah selesai dan tidak dapat diubah.', type: 'error');
                return;
            }

            $pesanan->items()->delete();

            $total = 0;
            $totalProfit = 0;
            $discountAmount = 0;

            $disc = null;
            if ($this->discount_id) {
                $disc = Discount::with('discountItems')->find($this->discount_id);
            }

            foreach ($this->pesanan as $p) {
                $menu = Menu::find($p['id']);
                if (!$menu) continue;

                $hargaJual = $p['harga'];
                $qty = $p['qty'];
                $subtotalItem = $hargaJual * $qty;
                $profitPerItem = ($hargaJual - $menu->h_pokok) * $qty;

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
                            $itemDiscountValue = $subtotalItem * ($disc->nilai_diskon / 100);
                            if ($disc->maksimum_diskon && $itemDiscountValue > $disc->maksimum_diskon) {
                                $itemDiscountValue = $disc->maksimum_diskon;
                            }
                        } elseif ($disc->jenis_diskon === 'nominal') {
                            $itemDiscountValue = $disc->nilai_diskon * $qty; 
                        }
                    }
                }

                $pesanan->items()->create([
                    'menus_id' => $p['id'],
                    'qty' => $qty,
                    'harga_satuan' => $hargaJual,
                    'subtotal' => $subtotalItem,
                    'discount_value' => $itemDiscountValue,
                    'profit' => $profitPerItem,
                    'catatan_item' => $p['catatan'] ?? null,
                ]);

                $total += $subtotalItem - $itemDiscountValue;
                $totalProfit += $profitPerItem - $itemDiscountValue;
            }

            if ($disc && $disc->is_active && $disc->scope === 'global') {
                if (
                    (!$disc->tanggal_mulai || $disc->tanggal_mulai <= now()) &&
                    (!$disc->tanggal_akhir || $disc->tanggal_akhir >= now())
                ) {
                    if (!is_null($disc->limit) && !is_null($disc->digunakan) && $disc->digunakan >= $disc->limit) {
                        // limit habis
                    } elseif ($disc->minimum_transaksi && $total < $disc->minimum_transaksi) {
                        // tidak memenuhi minimum transaksi
                    } else {
                        if ($disc->jenis_diskon === 'persentase') {
                            $discountAmount = $total * ($disc->nilai_diskon / 100);
                            if ($disc->maksimum_diskon && $discountAmount > $disc->maksimum_diskon) {
                                $discountAmount = $disc->maksimum_diskon;
                            }
                        } elseif ($disc->jenis_diskon === 'nominal') {
                            $discountAmount = $disc->nilai_diskon;
                        }
                    }
                }
            }

            $totalAfterDiscount = max(0, $total - $discountAmount);

            $pesanan->update([
                'mejas_id' => $this->mejas_id,
                'nama' => $this->nama_costumer,
                'member_id' => $this->member ? (\App\Models\Member::where('phone', $this->member)->value('id')) : null,
                'metode_pembayaran' => $this->metode_pembayaran ?? null,
                'discount_id' => $this->discount_id,
                'discount_value' => $discountAmount,
                'total' => $total,
                'total_profit' => $totalProfit,
                'uang_tunai' => $this->isCash ? $this->uang_tunai : 0,
                'kembalian' => $this->isCash ? $this->uang_tunai - $totalAfterDiscount : 0,
            ]);

            if ($pesanan->status === 'dibatalkan') {
                if ($pesanan->discount_id) {
                    $pesanan->discount->decrement('digunakan');
                }
            }

            DB::commit();
            $this->dispatch('showToast', type: 'success', message: 'Pesanan berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showToast', type: 'error', message: 'Gagal update pesanan: ' . $e->getMessage());
        }
    }

    public function saveOrder()
    {
        if (empty($this->pesanan)) {
            $this->dispatch('showToast', message: 'Pesanan masih kosong', type: 'error', title: 'Error');
            return;
        }

        if (! $this->nama_costumer) {
            $this->dispatch('showToast', message: 'Nama costumer tidak boleh kosong!', type: 'error', title: 'Error');
            return;
        }

        DB::beginTransaction();
        try {
            $randomString = strtoupper(Str::random(8));
            $tanggal = date('dm');
            $kodeFinal = $randomString . $tanggal;

            $pesanan = Pesanan::create([
                'kode' => $kodeFinal,
                'mejas_id' => $this->mejas_id,
                'nama' => $this->nama_costumer,
                'user_id' => Auth::id(),
                'member_id' => $this->member ? (\App\Models\Member::where('phone', $this->member)->value('id')) : null,
                'discount_id' => $this->discountId,
                'discount_value' => 0,
                'metode_pembayaran' => $this->metode_pembayaran ?? null,
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
            }

            foreach ($this->pesanan as $p) {
                $menu = Menu::find($p['id']);
                if (! $menu) continue;

                $hargaJual = $p['harga'];
                $qty = $p['qty'];
                $subtotalItem = $hargaJual * $qty;
                $profitPerItem = ($hargaJual - $menu->h_pokok) * $qty;

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
                            $itemDiscountValue = $subtotalItem * ($disc->nilai_diskon / 100);
                            if ($disc->maksimum_diskon && $itemDiscountValue > $disc->maksimum_diskon) {
                                $itemDiscountValue = $disc->maksimum_diskon;
                            }
                        } elseif ($disc->jenis_diskon === 'nominal') {
                            $itemDiscountValue = $disc->nilai_diskon * $qty; 
                        }
                    }
                }

                $existingItem = $pesanan->items()->where('menus_id', $p['id'])->first();

                if ($existingItem) {
                    $newQty = $existingItem->qty + $qty;
                    $existingItem->update([
                        'qty' => $newQty,
                        'subtotal' => ($newQty * $hargaJual) - $itemDiscountValue, // recalculating assuming all in same batch
                        'discount_value' => $itemDiscountValue, // simplify, this shouldn't really happen since cart keys are unique
                        'profit' => (($hargaJual - $menu->h_pokok) * $newQty) - $itemDiscountValue,
                    ]);
                } else {
                    $newItem = $pesanan->items()->create([
                        'menus_id' => $p['id'],
                        'qty' => $qty,
                        'harga_satuan' => $hargaJual,
                        'subtotal' => $subtotalItem - $itemDiscountValue,
                        'discount_value' => $itemDiscountValue,
                        'profit' => $profitPerItem - $itemDiscountValue,
                        'catatan_item' => null,
                    ]);

                    if (!empty($p['selected_options'])) {
                        $newItem->variants()->sync($p['selected_options']);
                    }
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
                            $discountAmount = $total * ($disc->nilai_diskon / 100);
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
                'discount_id' => $this->discountId,
                'nama' => $this->nama_costumer,
                'discount_value' => $discountAmount,
                'total' => $total,
                'total_profit' => $totalProfit,
                'uang_tunai' => $this->isCash ? $this->uang_tunai : 0,
                'kembalian' => $this->isCash ? $this->uang_tunai - $totalAfterDiscount : 0,
            ]);

            DB::commit();

            $this->pesanan = [];
            $this->mejas_id = null;
            $this->nama_costumer = '';

            $this->lastPesananId = $pesanan->id;
            $this->lastKodePesanan = $pesanan->kode;
            $this->lastTotalPesanan = $pesanan->total;

            $this->dispatch('showToast', message: 'Pesanan berhasil disimpan.', type: 'success', title: 'Success');
            if ($this->metode_pembayaran) {
                $this->dispatch('open-modal', name: 'order-success');
            }
        } catch (\Exception $e) {
                DB::rollBack();
                $this->dispatch('showToast', type: 'error', message: 'Gagal simpan pesanan: ' . $e->getMessage());
        }
    }
    

    private function reduceStock(Pesanan $pesanan)
    {
        foreach ($pesanan->items as $item) {
            $komposisi = MenuIngredients::where('menu_id', $item->menus_id)->get();
            foreach ($komposisi as $k) {
                $ingredient = Ingredients::find($k->ingredient_id);
                if (!$ingredient) continue;

                $totalOut = $k->qty * $item->qty;
                $before = $ingredient->stok;
                $after = $before - $totalOut;

                $ingredient->update(['stok' => $after]);

                RiwayatStock::create([
                    'ingredient_id' => $ingredient->id,
                    'kode' => strtoupper('OUT-' . Str::random(6)),
                    'qty' => $totalOut,
                    'qty_before' => $before,
                    'qty_after' => $after,
                    'tipe' => 'out',
                    'keterangan' => 'Pengurangan stok dari pesanan ' . $pesanan->kode,
                ]);
            }
        }
    }

    private function restoreStock(Pesanan $pesanan)
    {
        foreach ($pesanan->items as $item) {
            $komposisi = MenuIngredients::where('menu_id', $item->menus_id)->get();
            foreach ($komposisi as $k) {
                $ingredient = Ingredients::find($k->ingredient_id);
                if (!$ingredient) continue;

                $totalIn = $k->qty * $item->qty;
                $before = $ingredient->stok;
                $after = $before + $totalIn;

                $ingredient->update(['stok' => $after]);

                RiwayatStock::create([
                    'ingredient_id' => $ingredient->id,
                    'kode' => strtoupper('IN-' . Str::random(6)),
                    'qty' => $totalIn,
                    'qty_before' => $before,
                    'qty_after' => $after,
                    'tipe' => 'in',
                    'keterangan' => 'Pengembalian stok dari pembatalan pesanan ' . $pesanan->kode,
                ]);
            }
        }
    }
}
