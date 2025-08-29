<?php

namespace App\Livewire\Order;

use App\Models\Pesanan;
use Livewire\Component;

class TableOrder extends Component
{

    public $perPage = 10;
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public function resetSearch()
    {
        $this->reset('search');
    }

    public function delPesanan($id)
    {
        $order = Pesanan::find(base64_decode($id));
        if ($order) {
            if($order->status == 'selesai') {
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
            ->where('kode', 'like', '%' . $this->search . '%')
            ->orWhere('status', 'like', '%' . $this->search . '%')
            ->orWhere('metode_pembayaran', 'like', '%' . $this->search . '%')
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
        return view('livewire.order.table-order', [
            'orders' => $order
        ]);
    }
}
