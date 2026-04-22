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


class TableOrder extends Component
{
    use WithPagination;
    public $perPage = 10;
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    protected $paginationTheme = 'tailwind';
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

            // 2. Kurangi stok bahan
            foreach ($pesanan->items as $item) {

                // Ambil semua komposisi menu
                $komposisi = MenuIngredients::where('menu_id', $item->menus_id)->get();

                foreach ($komposisi as $k) {

                    $ingredient = Ingredients::find($k->ingredient_id);
                    if (! $ingredient) continue;

                    // Hitung total pemakaian bahan
                    $totalOut = $k->qty * $item->qty;

                    $before = $ingredient->stok;
                    $after = $before - $totalOut;

                    $ingredient->update([
                        'stok' => $after
                    ]);

                    // Catat ke riwayat stok (OUT)
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

        $this->dispatch('showToast', message: 'Pesanan Disajikan', type: 'success', title: 'Success');
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
            'orders' => $order
        ]);
    }
}
