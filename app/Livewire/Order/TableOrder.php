<?php

namespace App\Livewire\Order;

use DB;
use Carbon\Carbon;
use App\Models\Pesanan;
use Livewire\Component;
use App\Models\Ingredients;
use Illuminate\Support\Str;
use App\Models\RiwayatStock;
use Livewire\WithPagination;
use App\Models\MenuIngredients;
use App\Models\VariantOption;


class TableOrder extends Component
{
    use WithPagination;
    public $perPage = 10;
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    protected $paginationTheme = 'tailwind';

    public $detailOrder = null;
    public $selectedOrderItems = [];

    public function resetSearch()
    {
        $this->reset('search');
    }

    // public function saji($id)
    // {

    //     $pesanan = Pesanan::findOrFail(base64_decode($id));
    //     $pesanan->status =  $pesanan->status == 'diproses' ? 'selesai' : $this->status;
    //     $pesanan->save();

    //     $this->dispatch('showToast', message: 'Pesanan Disajikan', type: 'success', title: 'Success');
    // }

    public function saji($id)
    {
        // Cukup panggil relasinya
        $pesanan = Pesanan::with(['items', 'discount'])->findOrFail(base64_decode($id));

        // Jika belum pilih metode pembayaran, batalkan proses
        if ($pesanan->metode_pembayaran === null) {
            $this->dispatch('showToast', message: 'Metode pembayaran harus dipilih!', type: 'info', title: 'Info');
            return;
        }

        $statusSebelumnya = $pesanan->status;

        // Jika status sebelumnya DICAP sudah selesai → SKIP proses ke bawah
        if ($statusSebelumnya === 'selesai') {
            $this->dispatch('showToast', message: 'Pesanan sudah selesai sebelumnya.', type: 'info', title: 'Info');
            return;
        }

        // Update status pesanan
        $pesanan->status = $pesanan->status == 'diproses' ? 'selesai' : $this->status;
        $pesanan->save();

        // 🔥 Jika status baru saja berubah jadi selesai
        if ($pesanan->status === 'selesai') {
            
            // LOGIKA POINTS: Tambah poin ke member
            if ($pesanan->member_id) {
                // total_after_discount calculation:
                $totalAfterDiscount = max(0, $pesanan->total - $pesanan->discount_value);
                $earnedPoints = floor($totalAfterDiscount / 10000); // 1 point per 10k
                
                $member = \App\Models\Member::find($pesanan->member_id);
                if ($member) {
                    $member->increment('points', $earnedPoints);
                    $member->increment('total_pengeluaran', $totalAfterDiscount);
                }
            }

            // 1. PERBAIKAN DISKON: Gunakan increment() agar aman & menghindari error null
            // if ($pesanan->discount_id && $pesanan->discount) {
            //     $pesanan->discount->increment('digunakan');
            // }

            // 2. Kurangi stok bahan (Agregasi)
            $stockChanges = []; // [ingredient_id => qty]

            foreach ($pesanan->items as $item) {
                // A. Kumpulkan dari resep DASAR menu
                $komposisi = MenuIngredients::where('menu_id', $item->menus_id)->get();
                foreach ($komposisi as $k) {
                    if (!isset($stockChanges[$k->ingredient_id])) {
                        $stockChanges[$k->ingredient_id] = 0;
                    }
                    $stockChanges[$k->ingredient_id] += ($k->qty * $item->qty);
                }

                // B. Kumpulkan dari resep VARIAN
                $selectedVariantIds = $item->variants()->pluck('variant_options.id')->toArray();
                if (!empty($selectedVariantIds)) {
                    $variantOptions = VariantOption::with('ingredients')
                        ->whereIn('id', $selectedVariantIds)
                        ->get();

                    foreach ($variantOptions as $variant) {
                        foreach ($variant->ingredients as $vIngredient) {
                            if (!isset($stockChanges[$vIngredient->id])) {
                                $stockChanges[$vIngredient->id] = 0;
                            }
                            $stockChanges[$vIngredient->id] += ($vIngredient->pivot->qty * $item->qty);
                        }
                    }
                }
            }

            // C. Eksekusi pemotongan stok (Satu kali per bahan)
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

        $this->dispatch('showToast', message: 'Pesanan Disajikan', type: 'success', title: 'Success');
    }

    public function showDetail($encodedId)
    {
        $id = base64_decode($encodedId);
        $this->detailOrder = Pesanan::with([
            'items:id,pesanans_id,menus_id,qty,harga_satuan,subtotal',
            'items.menus:id,nama_menu',
            'items.variants',
            'user:id,name',
        ])->findOrFail($id);

        $this->selectedOrderItems = $this->detailOrder->items;
        $this->dispatch('open-modal', name: 'detail-order');
    }

    public function delPesanan($id)
    {
        $order = Pesanan::find(base64_decode($id));
        if ($order) {
            if ($order->status == 'selesai') {
                $this->dispatch('showToast', message: 'Pesanan Selesai Tidak Bisa Dihapus', type: 'warning', title: 'Warning');
                return;
            }
            $order->delete();
            $this->dispatch('showToast', message: 'Pesanan Berhasil diHapus', type: 'success', title: 'Success');
        } else {
            $this->dispatch('showToast', message: 'Pesanan Gagal Dihapus', type: 'warning', title: 'Warning');
        }
    }
    public $totalPerMetode = [];
    public $totalOmset;
    public function render()
    {
        $this->totalPerMetode = Pesanan::where('status', 'selesai')
            ->whereNotNull('metode_pembayaran')
            ->whereDate('created_at', Carbon::today())
            ->selectRaw('metode_pembayaran, SUM(total - discount_value) as total')
            ->groupBy('metode_pembayaran')
            ->pluck('total', 'metode_pembayaran')
            ->toArray();

        $this->totalOmset = Pesanan::where('status', 'selesai')
            ->where('metode_pembayaran', '!=', 'komplemen')
            ->whereNotNull('metode_pembayaran')
            ->whereDate('created_at', Carbon::today())
            ->sum(DB::raw('total - discount_value'));
        // $orders = $query->latest()->paginate($this->perPage);
        $order = Pesanan::query()
            ->whereDate('created_at', Carbon::today())
            ->where(function ($query) {
                $query->where('kode', 'like', '%' . $this->search . '%')
                    ->orWhere('status', 'like', '%' . $this->search . '%')
                    ->orWhere('metode_pembayaran', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.order.table-order', [
            'orders'             => $order,
            'title'              => 'Daftar Pesanan Hari Ini',
            'detailOrder'        => $this->detailOrder,
            'selectedOrderItems' => $this->selectedOrderItems,
        ])->layout('layouts.app', ['title' => 'Pesanan']);
    }
}
