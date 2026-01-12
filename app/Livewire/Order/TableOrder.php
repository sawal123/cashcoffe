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
        $pesanan = Pesanan::with('items')->findOrFail(base64_decode($id));

        // Jika sudah selesai sebelumnya -> jangan kurangi stok lagi
        $statusSebelumnya = $pesanan->status;
        if ($pesanan->metode_pembayaran === null) {
            $this->dispatch('showToast', message: 'Metode pembayaran harus dipilih!', type: 'info', title: 'Info');
            return;
        }
        // Update status
        $pesanan->status = $pesanan->status == 'diproses' ? 'selesai' : $this->status;
        $pesanan->save();

        // Jika status sebelumnya DICAP sudah selesai → SKIP pengurangan stok
        if ($statusSebelumnya === 'selesai') {
            $this->dispatch('showToast', message: 'Pesanan sudah selesai sebelumnya.', type: 'info', title: 'Info');
            return;
        }



        // 🔥 Jika status jadi selesai → Kurangi stok dapur
        if ($pesanan->status === 'selesai') {

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

                    // Update stok bahan
                    // $ingredient->update([
                    //     'stok' => max(0, $after)
                    // ]);
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
