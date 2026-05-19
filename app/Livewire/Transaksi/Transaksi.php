<?php

namespace App\Livewire\Transaksi;

use Carbon\Carbon;
use App\Models\Pesanan;
use Livewire\Component;
use App\Models\Ingredients;
use Illuminate\Support\Str;
use App\Models\RiwayatStock;
use Livewire\WithPagination;
use App\Models\MenuIngredients;
use App\Models\VariantOption;
use Illuminate\Support\Facades\DB;

class Transaksi extends Component
{
    use WithPagination;
    public $pembayaran = [];
    public $search = '';
    public $filterPembayaran = '';
    public $dateFrom;
    public $dateTo;
    public $perPage = 20;
    public $totalPerMetode = [];
    public $totalOmset = 0;
    protected $queryString = [
        'search' => ['except' => ''],
        'filterPembayaran' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public $selectedOrder;
    public $status;
    public $detailOrder;
    public $metode_pembayaran;

    public function mount()
    {
        $this->pembayaran = \App\Models\PaymentMethod::where('is_active', true)->get();
    }


    public function updating($field)
    {
        // reset pagination setiap kali filter berubah
        if (in_array($field, ['search', 'filterPembayaran', 'dateFrom', 'dateTo'])) {
            $this->resetPage();
        }
    }

    public $selectedOrderItems = [];

    public function editStatus($encodedId)
    {
        // TUTUP modal detail kalau terbuka
        $this->dispatch('close-modal', name: 'detail-order');

        $id = base64_decode($encodedId);

        $this->selectedOrder = Pesanan::select(
            'id',
            'kode',
            'status',
            'payment_method_id'
        )->findOrFail($id);

        $this->status = $this->selectedOrder->status;
        $this->metode_pembayaran = $this->selectedOrder->payment_method_id;

        $this->dispatch('open-modal', name: 'edit-status-order');
    }



    public function updateStatus()
    {
        $this->validate([
            'status' => 'required',
            'metode_pembayaran' => 'nullable',
        ]);

        // Ambil data pesanan LENGKAP + items
        $pesanan = Pesanan::with('items')->findOrFail($this->selectedOrder->id);

        $oldStatus = $pesanan->status;
        $newStatus = $this->status;

        // =========================
        // LOGIKA STOCK
        // =========================

        // dari diproses -> selesai (KURANGI STOK)
        if ($oldStatus === 'diproses' && $newStatus === 'selesai') {
            $this->reduceStock($pesanan);

            if ($pesanan->member_id) {
                $totalAfterDiscount = max(0, $pesanan->total - $pesanan->discount_value);
                $earnedPoints = floor($totalAfterDiscount / 10000); // 1 point per 10k
                $member = \App\Models\Member::find($pesanan->member_id);
                if ($member) {
                    $member->increment('points', $earnedPoints);
                    $member->increment('total_pengeluaran', $totalAfterDiscount);
                }
            }
        }

        // dari selesai -> diproses ATAU dibatalkan (KEMBALIKAN STOK)
        if ($oldStatus === 'selesai' && in_array($newStatus, ['diproses', 'dibatalkan'])) {
            $this->restoreStock($pesanan);

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


        // =========================
        // UPDATE STATUS PESANAN
        // =========================
        $pesanan->update([
            'status' => $newStatus,
            'payment_method_id' => $this->metode_pembayaran ?: null,
        ]);

        if ($pesanan->status === 'dibatalkan') {
            if ($pesanan->discount_id) {
                $pesanan->discount->decrement('digunakan');
            }
        }

        // =========================
        // UI FEEDBACK
        // =========================
        $this->dispatch('close-modal', name: 'edit-status-order');
        $this->dispatch(
            'showToast',
            type: 'success',
            message: 'Transaksi berhasil diperbarui'
        );
    }



    private function reduceStock(Pesanan $pesanan)
    {
        $stockChanges = []; // [ingredient_id => ['qty' => total, 'name' => name]]

        foreach ($pesanan->items as $item) {
            // 1. Kumpulkan dari resep DASAR menu
            $komposisi = MenuIngredients::where('menu_id', $item->menus_id)->get();
            foreach ($komposisi as $k) {
                if (!isset($stockChanges[$k->ingredient_id])) {
                    $stockChanges[$k->ingredient_id] = ['qty' => 0];
                }
                $stockChanges[$k->ingredient_id]['qty'] += ($k->qty * $item->qty);
            }

            // 2. Kumpulkan dari resep VARIAN
            $selectedVariantIds = $item->variants()->pluck('variant_options.id')->toArray();
            if (!empty($selectedVariantIds)) {
                $variantOptions = VariantOption::with('ingredients')
                    ->whereIn('id', $selectedVariantIds)
                    ->get();

                foreach ($variantOptions as $variant) {
                    foreach ($variant->ingredients as $vIngredient) {
                        if (!isset($stockChanges[$vIngredient->id])) {
                            $stockChanges[$vIngredient->id] = ['qty' => 0];
                        }
                        $stockChanges[$vIngredient->id]['qty'] += ($vIngredient->pivot->qty * $item->qty);
                    }
                }
            }
        }

        // 3. Eksekusi pemotongan stok (Agregat)
        foreach ($stockChanges as $ingredientId => $data) {
            $ingredient = Ingredients::find($ingredientId);
            if (!$ingredient)
                continue;

            $before = $ingredient->stok;
            $after = $before - $data['qty'];

            $ingredient->update(['stok' => $after]);

            RiwayatStock::create([
                'ingredient_id' => $ingredient->id,
                'kode' => strtoupper('OUT-' . Str::random(6)),
                'qty' => $data['qty'],
                'qty_before' => $before,
                'qty_after' => $after,
                'tipe' => 'out',
                'keterangan' => 'Akumulasi resep: pesanan ' . $pesanan->kode,
            ]);
        }
    }

    private function restoreStock(Pesanan $pesanan)
    {
        $stockChanges = []; // [ingredient_id => ['qty' => total]]

        foreach ($pesanan->items as $item) {
            // 1. Kumpulkan dari resep DASAR menu
            $komposisi = MenuIngredients::where('menu_id', $item->menus_id)->get();
            foreach ($komposisi as $k) {
                if (!isset($stockChanges[$k->ingredient_id])) {
                    $stockChanges[$k->ingredient_id] = ['qty' => 0];
                }
                $stockChanges[$k->ingredient_id]['qty'] += ($k->qty * $item->qty);
            }

            // 2. Kumpulkan dari resep VARIAN
            $selectedVariantIds = $item->variants()->pluck('variant_options.id')->toArray();
            if (!empty($selectedVariantIds)) {
                $variantOptions = VariantOption::with('ingredients')
                    ->whereIn('id', $selectedVariantIds)
                    ->get();

                foreach ($variantOptions as $variant) {
                    foreach ($variant->ingredients as $vIngredient) {
                        if (!isset($stockChanges[$vIngredient->id])) {
                            $stockChanges[$vIngredient->id] = ['qty' => 0];
                        }
                        $stockChanges[$vIngredient->id]['qty'] += ($vIngredient->pivot->qty * $item->qty);
                    }
                }
            }
        }

        // 3. Eksekusi pengembalian stok (Agregat)
        foreach ($stockChanges as $ingredientId => $data) {
            $ingredient = Ingredients::find($ingredientId);
            if (!$ingredient)
                continue;

            $before = $ingredient->stok;
            $after = $before + $data['qty'];

            $ingredient->update(['stok' => $after]);

            RiwayatStock::create([
                'ingredient_id' => $ingredient->id,
                'kode' => strtoupper('IN-' . Str::random(6)),
                'qty' => $data['qty'],
                'qty_before' => $before,
                'qty_after' => $after,
                'tipe' => 'in',
                'keterangan' => 'Pengembalian akumulasi: batal pesanan ' . $pesanan->kode,
            ]);
        }
    }






    public function showDetail($encodedId)
    {
        $this->dispatch('close-modal', name: 'edit-status-order');
        $id = base64_decode($encodedId);
        $this->detailOrder = Pesanan::with([
            'items:id,pesanans_id,menus_id,qty,harga_satuan,subtotal',
            'items.menus:id,nama_menu',
            'items.variants',
        ])->findOrFail($id);

        $this->selectedOrderItems = $this->detailOrder->items;
        // dd($this->detailOrder);

        $this->dispatch('open-modal', name: 'detail-order');
    }



    public function render()
    {
        $query = Pesanan::query()
            ->with(['user', 'paymentMethod'])

            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('kode', 'like', "%{$this->search}%")
                        ->orWhere('nama', 'like', "%{$this->search}%")
                        ->orWhereHas('user', function ($userQuery) {
                            $userQuery->where('name', 'like', "%{$this->search}%");
                        });
                });
            })

            ->when($this->filterPembayaran, function ($q) {
                if ($this->filterPembayaran === 'belum') {
                    $q->whereNull('payment_method_id');
                } else {
                    $q->where('payment_method_id', $this->filterPembayaran);
                }
            })

            // 👉 JIKA USER PILIH TANGGAL
            ->when($this->dateFrom && $this->dateTo, function ($q) {
                $q->whereBetween('created_at', [
                    Carbon::parse($this->dateFrom)->startOfDay(),
                    Carbon::parse($this->dateTo)->endOfDay(),
                ]);
            })

            // 👉 DEFAULT: HARI INI
            ->when(!$this->dateFrom && !$this->dateTo, function ($q) {
                $q->whereDate('created_at', Carbon::today());
            });

        // Hitung total per metode pembayaran
        $this->totalPerMetode = $query->clone()
            ->where('status', 'selesai')
            ->join('payment_methods', 'pesanans.payment_method_id', '=', 'payment_methods.id')
            ->selectRaw('payment_methods.nama_metode, SUM(total - discount_value) as total')
            ->groupBy('payment_methods.nama_metode')
            ->pluck('total', 'payment_methods.nama_metode')
            ->toArray();

        // Hitung total omset keseluruhan
        $this->totalOmset = $query->clone()
            ->where('status', 'selesai')
            ->where('status', '!=', 'dibatalkan')
            ->leftJoin('payment_methods', 'pesanans.payment_method_id', '=', 'payment_methods.id')
            ->where(function ($q) {
                $q->where('payment_methods.kode_metode', '!=', 'komplemen')
                  ->orWhereNull('pesanans.payment_method_id');
            })
            ->sum(DB::raw('total - discount_value'));
        $orders = $query->latest()->paginate($this->perPage);

        return view('livewire.transaksi.transaksi', [
            'orders' => $orders
        ])->layout('layouts.app', ['title' => 'Transaksi']);
    }
}
