<?php

namespace App\Livewire\Order;

use Illuminate\Support\Facades\DB;
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
        $decodedId = base64_decode($id);

        try {
            DB::transaction(function () use ($decodedId) {
                $pesanan = Pesanan::where('id', $decodedId)
                    ->lockForUpdate()
                    ->with(['items', 'discount'])
                    ->firstOrFail();

                if ($pesanan->payment_method_id === null) {
                    $this->dispatch('showToast', message: 'Metode pembayaran harus dipilih!', type: 'info', title: 'Info');
                    return;
                }

                if ($pesanan->status === 'selesai') {
                    $this->dispatch('showToast', message: 'Pesanan sudah selesai sebelumnya.', type: 'info', title: 'Info');
                    return;
                }

                if ($pesanan->status === 'dibatalkan') {
                    $this->dispatch('showToast', message: 'Pesanan yang sudah dibatalkan tidak dapat disajikan.', type: 'error', title: 'Error');
                    return;
                }

                if ($pesanan->status !== 'diproses') {
                    $this->dispatch('showToast', message: 'Transisi status tidak valid.', type: 'error', title: 'Error');
                    return;
                }

                $pesanan->status = 'selesai';
                $pesanan->save();

                if ($pesanan->member_id) {
                    $totalAfterDiscount = max(0, $pesanan->total - $pesanan->discount_value);
                    $earnedPoints = floor($totalAfterDiscount / 10000);

                    $member = \App\Models\Member::find($pesanan->member_id);
                    if ($member) {
                        $member->increment('points', $earnedPoints);
                        $member->increment('total_pengeluaran', $totalAfterDiscount);
                    }
                }

                $stockChanges = [];

                foreach ($pesanan->items as $item) {
                    $komposisi = MenuIngredients::where('menu_id', $item->menus_id)->get();
                    foreach ($komposisi as $k) {
                        if (!isset($stockChanges[$k->ingredient_id])) {
                            $stockChanges[$k->ingredient_id] = 0;
                        }
                        $stockChanges[$k->ingredient_id] += ($k->qty * $item->qty);
                    }

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

                $this->dispatch('showToast', message: 'Pesanan Disajikan', type: 'success', title: 'Success');
            });
        } catch (\Exception $e) {
            $this->dispatch('showToast', message: 'Gagal menyajikan pesanan: ' . $e->getMessage(), type: 'error', title: 'Error');
        }
    }

    public function showDetail($encodedId)
    {
        $id = base64_decode($encodedId);
        $this->detailOrder = Pesanan::with([
            'items:id,pesanans_id,menus_id,qty,harga_satuan,subtotal',
            'items.menus:id,nama_menu',
            'items.variants',
            'user:id,name',
            'paymentMethod',
        ])->findOrFail($id);

        $this->selectedOrderItems = $this->detailOrder->items;
        $this->dispatch('open-modal', name: 'detail-order');
    }

    public function delPesanan($id)
    {
        $decodedId = base64_decode($id);

        try {
            DB::transaction(function () use ($decodedId) {
                $order = Pesanan::where('id', $decodedId)->lockForUpdate()->first();
                if ($order) {
                    if ($order->status !== 'diproses') {
                        $this->dispatch('showToast', message: "Pesanan dengan status '{$order->status}' tidak dapat dihapus", type: 'warning', title: 'Warning');
                        return;
                    }
                    $order->delete();
                    $this->dispatch('showToast', message: 'Pesanan Berhasil diHapus', type: 'success', title: 'Success');
                } else {
                    $this->dispatch('showToast', message: 'Pesanan Gagal Dihapus', type: 'warning', title: 'Warning');
                }
            });
        } catch (\Exception $e) {
            $this->dispatch('showToast', message: 'Gagal menghapus pesanan: ' . $e->getMessage(), type: 'error', title: 'Error');
        }
    }
    public $totalPerMetode = [];
    public $totalOmset;
    public function render()
    {
        $this->totalPerMetode = Pesanan::where('status', 'selesai')
            ->join('payment_methods', 'pesanans.payment_method_id', '=', 'payment_methods.id')
            ->whereDate('pesanans.created_at', Carbon::today())
            ->selectRaw('payment_methods.nama_metode, SUM(total - discount_value) as total')
            ->groupBy('payment_methods.nama_metode')
            ->pluck('total', 'payment_methods.nama_metode')
            ->toArray();

        $this->totalOmset = Pesanan::where('status', 'selesai')
            ->leftJoin('payment_methods', 'pesanans.payment_method_id', '=', 'payment_methods.id')
            ->where(function ($q) {
                $q->where('payment_methods.kode_metode', '!=', 'komplemen')
                    ->orWhereNull('pesanans.payment_method_id');
            })
            ->whereDate('pesanans.created_at', Carbon::today())
            ->sum(DB::raw('total - discount_value'));
        // $orders = $query->latest()->paginate($this->perPage);
        $order = Pesanan::query()
            ->with(['salesChannel', 'user', 'paymentMethod'])
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
