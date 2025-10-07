<?php

namespace App\Livewire\Order;

use Carbon\Carbon;
use App\Models\Meja;
use App\Models\Menu;
use App\Models\Pesanan;
use Livewire\Component;
use App\Models\PesananItem;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreateOrder extends Component
{
    use WithPagination;

    public $url = "order";
    public $orderId = null;
    public $title = 'Order / Create';
    public $submit = 'saveOrder';
    public $search = '';
    public $teks = 'Proses';
    public $mejas = [];
    public $pesanan = [];
    public $pembayaran = ['tunai', 'qris', 'kartu'];
    public $mejas_id, $perPage = 4; // default 10
    public $metode_pembayaran = null; // default
    public $status = null; // default

    protected $paginationTheme = 'tailwind'; // bisa juga 'bootstrap' sesuai css framework

    public function editOrder($id)
    {
        $this->orderId = $id;


        $pesanan = Pesanan::with('items')->findOrFail($id);
        $this->mejas_id = $pesanan->mejas_id;
        $this->metode_pembayaran = $pesanan->metode_pembayaran;
        $this->status = $pesanan->status;

        $this->pesanan = $pesanan->items->mapWithKeys(function ($item) {
            return [
                $item->menus_id => [
                    'id'        => $item->menus_id,
                    'nama_menu' => $item->menu->nama_menu ?? '',
                    'harga'     => (int) $item->harga_satuan,
                    'gambar'    => $item->menu->gambar ?? '',
                    'qty'       => $item->qty,
                    'catatan'   => $item->catatan_item,
                    'status'    => $item->status,
                ]
            ];
        })->toArray();
        // dd($pesanan);
    }

    public function updateOrder()
    {
        if (! $this->orderId) return;
        DB::beginTransaction();
        try {
            $pesanan = Pesanan::findOrFail($this->orderId);
            // dd($pesanan);
            $pesanan->items()->delete();

            foreach ($this->pesanan as $p) {
                $pesanan->items()->create([
                    'menus_id'     => $p['id'],
                    'qty'          => $p['qty'],
                    'harga_satuan' => $p['harga'],
                    'subtotal'     => $p['harga'] * $p['qty'],
                    'catatan_item' => $p['catatan'] ?? null,
                ]);
            }
            // dd($this->status);
            $pesanan->update([
                'mejas_id'          => $this->mejas_id,
                'metode_pembayaran' => $this->metode_pembayaran ?? null,
                'status'            => $this->status == 'diproses' ? 'selesai' : 'diproses',
                'total'             => $pesanan->items()->sum('subtotal'),
            ]);
            $this->status = $pesanan->status;

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
            $this->dispatch('showToast', message: 'Pesanan Masih Kosong', type: 'success', title: 'Success');
            return;
        }
        if (! $this->mejas_id) {
            $this->dispatch('showToast', message: 'Meja harus dipilih', type: 'error', title: 'Error');
            return;
        }

        DB::beginTransaction();
        try {
            $totalBaru = collect($this->pesanan)->sum(fn($p) => $p['harga'] * $p['qty']);

            $pesanan = Pesanan::where('mejas_id', $this->mejas_id)
                ->where('status', '!=', 'selesai')
                ->where('status', '!=', 'dibatalkan')
                ->whereDate('created_at', Carbon::today())
                ->first();

            if (!$pesanan) {
                $pesanan = Pesanan::create([
                    'kode'              => strtoupper(Str::random(8)),
                    'mejas_id'          => $this->mejas_id,
                    'user_id'          => Auth::user()->id,
                    'metode_pembayaran' => $this->metode_pembayaran ?? null,
                    'total'             => 0,
                    'catatan'           => null,
                ]);
            }

            foreach ($this->pesanan as $p) {
                $existingItem = $pesanan->items()->where('menus_id', $p['id'])->first();

                if ($existingItem) {
                    $existingItem->update([
                        'qty'      => $existingItem->qty + $p['qty'],
                        'subtotal' => ($existingItem->qty + $p['qty']) * $existingItem->harga_satuan,
                    ]);
                } else {
                    $pesanan->items()->create([
                        'menus_id'     => $p['id'],
                        'qty'          => $p['qty'],
                        'harga_satuan' => $p['harga'],
                        'subtotal'     => $p['harga'] * $p['qty'],
                        'catatan_item' => null,
                    ]);
                }
            }

            $pesanan->update([
                'total' => $pesanan->items()->sum('subtotal'),
            ]);

            DB::commit();
            $this->pesanan = [];
            $this->mejas_id = null;
            $this->dispatch('showToast', message: 'Menu berhasil dipesan', type: 'success', title: 'Success');
            // dd($pesanan->id);
            return redirect()->route('struk.print', base64_encode($pesanan->id));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showToast', type: 'error', message: 'Gagal simpan pesanan: ' . $e->getMessage());
        }
    }
    public function mount()
    {
        if ($this->orderId) {
            $this->submit = 'updateOrder';
            $this->teks = 'Update';
            $this->editOrder(base64_decode($this->orderId));
        }
        $this->mejas = Meja::all();
    }

    // reset halaman setiap kali search berubah
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function addPesanan($id)
    {
        $item = Menu::find($id); // ambil langsung dari DB karena menu sekarang dipaginate
        if (! $item) return;

        if (isset($this->pesanan[$id])) {
            $this->pesanan[$id]['qty']++;
        } else {
            $this->pesanan[$id] = [
                'id'        => $item->id,
                'nama_menu' => $item->nama_menu,
                'harga'     => (int) $item->harga,
                'gambar'    => $item->gambar,
                'qty'       => 1,
            ];
        }
    }

    public function increment($id)
    {
        $this->updateQty($id, 1);
    }
    public function decrement($id)
    {
        $this->updateQty($id, -1);
    }

    private function updateQty($id, $delta)
    {
        if (! isset($this->pesanan[$id])) return;

        $newQty = $this->pesanan[$id]['qty'] + $delta;

        if ($newQty > 0) {
            $this->pesanan[$id]['qty'] = $newQty;
        } else {
            unset($this->pesanan[$id]);
        }
    }

    public function getTotalProperty()
    {
        return collect($this->pesanan)->sum(fn($p) => $p['harga'] * $p['qty']);
    }

    public function render()
    {
        $menus = Menu::where('is_active', 1)
            ->where('nama_menu', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage); // misal 8 item per halaman

        return view('livewire.order.create-order', [
            'menus' => $menus,
            'orderId' => $this->orderId,
            'status' => $this->status,
        ]);
    }
}
