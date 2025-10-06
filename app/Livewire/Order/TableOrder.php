<?php

namespace App\Livewire\Order;

use Carbon\Carbon;
use App\Models\Pesanan;
use Livewire\Component;
use Livewire\WithPagination;

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

    public function saji($id)
    {

        $pesanan = Pesanan::findOrFail(base64_decode($id));
        $pesanan->status =  $pesanan->status == 'diproses' ? 'selesai' : $this->status;
        $pesanan->save();

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
    public function render()
    {
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
