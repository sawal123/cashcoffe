<?php

namespace App\Livewire\Transaksi;

use App\Models\Ingredients;
use App\Models\MenuIngredients;
use App\Models\Pesanan;
use App\Models\RiwayatStock;
use App\Models\VariantOption;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Transaksi extends Component
{
    use WithPagination;

    public $pembayaran = [];

    public $search = '';

    public $filterPembayaran = '';

    public $dateFrom;

    public $dateTo;

    public $dateRange = '';

    public $perPage = 20;

    public $totalPerMetode = [];

    public $totalPerChannel = [];

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

    public $selectedOrderItems = [];

    public function mount()
    {
        $this->pembayaran = \App\Models\PaymentMethod::where('is_active', true)->get();
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->syncDateRangeLabel();
    }

    public function updating($field)
    {
        if (in_array($field, ['search', 'filterPembayaran', 'dateFrom', 'dateTo', 'dateRange'])) {
            $this->resetPage();
        }
    }

    public function updatedDateFrom(): void
    {
        $this->normalizeDateRange();
    }

    public function updatedDateTo(): void
    {
        $this->normalizeDateRange();
    }

    public function setDateRange(?string $from = '', ?string $to = '', ?string $label = ''): void
    {
        $this->dateFrom = $from ?: '';
        $this->dateTo = $to ?: $this->dateFrom;
        $this->dateRange = $label ?: '';
        $this->normalizeDateRange();
        $this->resetPage();
    }

    public function resetDateRange(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->dateRange = '';
        $this->resetPage();
        $this->dispatch('transaksi-date-range-reset');
    }

    public function editStatus($encodedId)
    {
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

        $pesanan = Pesanan::with('items')->findOrFail($this->selectedOrder->id);

        $oldStatus = $pesanan->status;
        $newStatus = $this->status;

        if ($oldStatus === 'diproses' && $newStatus === 'selesai') {
            $this->reduceStock($pesanan);

            if ($pesanan->member_id) {
                $totalAfterDiscount = max(0, $pesanan->total - $pesanan->discount_value);
                $earnedPoints = floor($totalAfterDiscount / 10000);
                $member = \App\Models\Member::find($pesanan->member_id);
                if ($member) {
                    $member->increment('points', $earnedPoints);
                    $member->increment('total_pengeluaran', $totalAfterDiscount);
                }
            }
        }

        if ($oldStatus === 'selesai' && in_array($newStatus, ['diproses', 'dibatalkan'])) {
            $this->restoreStock($pesanan);

            if ($pesanan->member_id) {
                $totalAfterDiscount = max(0, $pesanan->total - $pesanan->discount_value);
                $earnedPoints = floor($totalAfterDiscount / 10000);
                $member = \App\Models\Member::find($pesanan->member_id);
                if ($member) {
                    $member->decrement('points', $earnedPoints);
                    $member->decrement('total_pengeluaran', $totalAfterDiscount);
                }
            }
        }

        $pesanan->update([
            'status' => $newStatus,
            'payment_method_id' => $this->metode_pembayaran ?: null,
        ]);

        if ($pesanan->status === 'dibatalkan' && $pesanan->discount_id) {
            $pesanan->discount->decrement('digunakan');
        }

        $this->dispatch('close-modal', name: 'edit-status-order');
        $this->dispatch(
            'showToast',
            type: 'success',
            message: 'Transaksi berhasil diperbarui'
        );
    }

    private function reduceStock(Pesanan $pesanan)
    {
        $stockChanges = [];

        foreach ($pesanan->items as $item) {
            $komposisi = MenuIngredients::where('menu_id', $item->menus_id)->get();
            foreach ($komposisi as $k) {
                if (! isset($stockChanges[$k->ingredient_id])) {
                    $stockChanges[$k->ingredient_id] = ['qty' => 0];
                }
                $stockChanges[$k->ingredient_id]['qty'] += ($k->qty * $item->qty);
            }

            $selectedVariantIds = $item->variants()->pluck('variant_options.id')->toArray();
            if (! empty($selectedVariantIds)) {
                $variantOptions = VariantOption::with('ingredients')
                    ->whereIn('id', $selectedVariantIds)
                    ->get();

                foreach ($variantOptions as $variant) {
                    foreach ($variant->ingredients as $vIngredient) {
                        if (! isset($stockChanges[$vIngredient->id])) {
                            $stockChanges[$vIngredient->id] = ['qty' => 0];
                        }
                        $stockChanges[$vIngredient->id]['qty'] += ($vIngredient->pivot->qty * $item->qty);
                    }
                }
            }
        }

        foreach ($stockChanges as $ingredientId => $data) {
            $ingredient = Ingredients::find($ingredientId);
            if (! $ingredient) {
                continue;
            }

            $before = $ingredient->stok;
            $after = $before - $data['qty'];

            $ingredient->update(['stok' => $after]);

            RiwayatStock::create([
                'ingredient_id' => $ingredient->id,
                'kode' => strtoupper('OUT-'.Str::random(6)),
                'qty' => $data['qty'],
                'qty_before' => $before,
                'qty_after' => $after,
                'tipe' => 'out',
                'keterangan' => 'Akumulasi resep: pesanan '.$pesanan->kode,
            ]);
        }
    }

    private function restoreStock(Pesanan $pesanan)
    {
        $stockChanges = [];

        foreach ($pesanan->items as $item) {
            $komposisi = MenuIngredients::where('menu_id', $item->menus_id)->get();
            foreach ($komposisi as $k) {
                if (! isset($stockChanges[$k->ingredient_id])) {
                    $stockChanges[$k->ingredient_id] = ['qty' => 0];
                }
                $stockChanges[$k->ingredient_id]['qty'] += ($k->qty * $item->qty);
            }

            $selectedVariantIds = $item->variants()->pluck('variant_options.id')->toArray();
            if (! empty($selectedVariantIds)) {
                $variantOptions = VariantOption::with('ingredients')
                    ->whereIn('id', $selectedVariantIds)
                    ->get();

                foreach ($variantOptions as $variant) {
                    foreach ($variant->ingredients as $vIngredient) {
                        if (! isset($stockChanges[$vIngredient->id])) {
                            $stockChanges[$vIngredient->id] = ['qty' => 0];
                        }
                        $stockChanges[$vIngredient->id]['qty'] += ($vIngredient->pivot->qty * $item->qty);
                    }
                }
            }
        }

        foreach ($stockChanges as $ingredientId => $data) {
            $ingredient = Ingredients::find($ingredientId);
            if (! $ingredient) {
                continue;
            }

            $before = $ingredient->stok;
            $after = $before + $data['qty'];

            $ingredient->update(['stok' => $after]);

            RiwayatStock::create([
                'ingredient_id' => $ingredient->id,
                'kode' => strtoupper('IN-'.Str::random(6)),
                'qty' => $data['qty'],
                'qty_before' => $before,
                'qty_after' => $after,
                'tipe' => 'in',
                'keterangan' => 'Pengembalian akumulasi: batal pesanan '.$pesanan->kode,
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
            ->when($this->dateFrom && $this->dateTo, function ($q) {
                $q->whereBetween('pesanans.created_at', [
                    Carbon::parse($this->dateFrom)->startOfDay(),
                    Carbon::parse($this->dateTo)->endOfDay(),
                ]);
            })
            ->when(! $this->dateFrom && ! $this->dateTo, function ($q) {
                $q->whereDate('pesanans.created_at', Carbon::today());
            });

        $this->totalPerMetode = $query->clone()
            ->where('status', 'selesai')
            ->join('payment_methods', 'pesanans.payment_method_id', '=', 'payment_methods.id')
            ->selectRaw('payment_methods.nama_metode, SUM(total - discount_value) as total')
            ->groupBy('payment_methods.nama_metode')
            ->pluck('total', 'payment_methods.nama_metode')
            ->toArray();

        $this->totalPerChannel = $query->clone()
            ->where('status', 'selesai')
            ->join('sales_channels', 'pesanans.sales_channel_id', '=', 'sales_channels.id')
            ->selectRaw('sales_channels.nama_channel, SUM(total - discount_value) as total')
            ->groupBy('sales_channels.nama_channel')
            ->pluck('total', 'sales_channels.nama_channel')
            ->toArray();

        $this->totalOmset = $query->clone()
            ->where('status', 'selesai')
            ->where('status', '!=', 'dibatalkan')
            ->leftJoin('payment_methods', 'pesanans.payment_method_id', '=', 'payment_methods.id')
            ->where(function ($q) {
                $q->where('payment_methods.kode_metode', '!=', 'komplemen')
                    ->orWhereNull('pesanans.payment_method_id');
            })
            ->sum(DB::raw('total - discount_value'));

        $orders = $query->latest('pesanans.created_at')->paginate($this->perPage);

        return view('livewire.transaksi.transaksi', [
            'orders' => $orders,
        ])->layout('layouts.app', ['title' => 'Transaksi']);
    }

    private function normalizeDateRange(): void
    {
        $from = $this->validDate($this->dateFrom);
        $to = $this->validDate($this->dateTo);

        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $this->dateFrom = $from?->toDateString() ?? '';
        $this->dateTo = $to?->toDateString() ?? '';
        $this->syncDateRangeLabel();
    }

    private function syncDateRangeLabel(): void
    {
        if ($this->dateRange && str_contains($this->dateRange, ' to ')) {
            return;
        }

        if ($this->dateFrom && $this->dateTo) {
            $this->dateRange = $this->dateFrom.' to '.$this->dateTo;
        } elseif ($this->dateFrom) {
            $this->dateRange = $this->dateFrom;
        } else {
            $this->dateRange = '';
        }
    }

    private function validDate(?string $date): ?Carbon
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', trim($date))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
