<?php

namespace App\Livewire\Order;

use Carbon\Carbon;
use App\Models\Meja;
use App\Models\Menu;
use App\Models\Member;
use App\Models\Pesanan;
use Livewire\Component;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Ingredients;
use Illuminate\Support\Str;
use App\Models\RiwayatStock;
use Livewire\WithPagination;
use App\Models\MenuIngredients;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreateOrder extends Component
{
    use WithPagination;

    public $url = 'order';

    public $orderId = null;

    public $title = 'Order / Create';

    public $submit = 'saveOrder';

    public $search = '';

    public $discount = '';
    public $member = '';

    public $teks = 'Proses';

    public $mejas = [];

    public $nama_costumer = '';

    public $pesanan = [];

    public $pembayaran = ['tunai', 'qris', 'transfer', 'kartu', 'shopeefood', 'gofood', 'grabfood', 'komplemen'];

    public $mejas_id;

    public $discountId;
    // public $memMessage;

    public $perPage = 12; // default 10

    public $metode_pembayaran = null; // default

    public $status = null; // default

    protected $paginationTheme = 'tailwind'; // bisa juga 'bootstrap' sesuai css framework

    public function editOrder($id)
    {
        $this->orderId = $id;

        $pesanan = Pesanan::with(['items.menu', 'discount'])->findOrFail($id);
        $this->mejas_id = $pesanan->mejas_id;
        $this->metode_pembayaran = $pesanan->metode_pembayaran;
        $this->status = $pesanan->status;

        $this->discountId = $pesanan->discount_id;
        $this->nama_costumer = $pesanan->nama;
        $this->discount = $pesanan->discount->kode_diskon ?? null;
        $this->pesanan = $pesanan->items->mapWithKeys(function ($item) {
            return [
                $item->menus_id => [
                    'id' => $item->menus_id,
                    'nama_menu' => $item->menu->nama_menu ?? '',
                    'harga' => (int) $item->harga_satuan,
                    'gambar' => $item->menu->gambar ?? '',
                    'qty' => $item->qty,
                    'catatan' => $item->catatan_item,
                    'status' => $item->status,
                ],
            ];
        })->toArray();
    }

    public function updateOrder()
    {
        if (!$this->orderId) return;

        DB::beginTransaction();

        try {
            $pesanan = Pesanan::with('items')->findOrFail($this->orderId);
            $statusLama = $pesanan->status;

            // ❗ Jika statusLama = selesai → kembalikan stok dulu sebelum update item
            if ($statusLama === 'selesai') {
                $this->restoreStock($pesanan);
            }

            // Hapus item lama
            $pesanan->items()->delete();

            // Tambah item baru
            foreach ($this->pesanan as $p) {
                $pesanan->items()->create([
                    'menus_id' => $p['id'],
                    'qty' => $p['qty'],
                    'harga_satuan' => $p['harga'],
                    'subtotal' => $p['harga'] * $p['qty'],
                    'catatan_item' => $p['catatan'] ?? null,
                ]);
            }

            // Update pesanan
            $pesanan->update([
                'mejas_id' => $this->mejas_id,
                'nama' => $this->nama_costumer,
                'metode_pembayaran' => $this->metode_pembayaran ?? null,
                'status' => $this->status,
                'total' => $pesanan->items()->sum('subtotal'),
            ]);

            // ❗ Jika statusBaru = selesai → kurangi stok berdasarkan item BARU
            if ($this->status === 'selesai') {
                $this->reduceStock($pesanan);
            }

            DB::commit();

            $this->status = $pesanan->status;

            $this->dispatch('showToast', type: 'success', message: 'Pesanan berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showToast', type: 'error', message: 'Gagal update pesanan: ' . $e->getMessage());
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

                $ingredient->update(['stok' => max(0, $after)]);

                RiwayatStock::create([
                    'ingredient_id' => $ingredient->id,
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

                // Kembalikan stok
                $ingredient->update(['stok' => $after]);

                // Simpan riwayat masuk
                RiwayatStock::create([
                    'ingredient_id' => $ingredient->id,
                    'qty' => $totalIn,
                    'qty_before' => $before,
                    'qty_after' => $after,
                    'tipe' => 'in',
                    'keterangan' => 'Pengembalian stok dari pembatalan pesanan ' . $pesanan->kode,
                ]);
            }
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
            $tanggal = date('dm'); // Format tanggal: ddmm
            $kodeFinal = $randomString . $tanggal;



            $pesanan = Pesanan::create([
                'kode' => $kodeFinal,
                'mejas_id' => $this->mejas_id,
                'nama' => $this->nama_costumer,
                'user_id' => Auth::id(),
                'discount_id' => $this->discountId,
                'discount_value' => 0,
                'metode_pembayaran' => $this->metode_pembayaran ?? null,
                'total' => 0,
                'total_profit' => 0,
                'catatan' => null,
            ]);

            foreach ($this->pesanan as $p) {
                $menu = Menu::find($p['id']);
                if (! $menu) {
                    continue;
                }

                $hargaJual = $menu->h_promo > 0 ? $menu->h_promo : $menu->harga;
                $profitPerItem = ($hargaJual - $menu->h_pokok) * $p['qty'];

                $existingItem = $pesanan->items()->where('menus_id', $p['id'])->first();

                if ($existingItem) {
                    $newQty = $existingItem->qty + $p['qty'];
                    $existingItem->update([
                        'qty' => $newQty,
                        'subtotal' => $newQty * $hargaJual,
                        'profit' => ($hargaJual - $menu->h_pokok) * $newQty,
                    ]);
                } else {
                    $pesanan->items()->create([
                        'menus_id' => $p['id'],
                        'qty' => $p['qty'],
                        'harga_satuan' => $hargaJual,
                        'subtotal' => $hargaJual * $p['qty'],
                        'profit' => $profitPerItem,
                        'catatan_item' => null,
                    ]);
                }
            }

            // Hitung total & profit
            $total = $pesanan->items()->sum('subtotal');
            $totalProfit = $pesanan->items()->sum('profit');
            $discountAmount = 0;

            // Terapkan diskon jika ada
            if ($this->discountId) {
                $disc = Discount::find($this->discountId);

                if (
                    $disc && $disc->is_active &&
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



            $totalAfterDiscount = max(0, $total);

            // Update total pesanan di database
            $pesanan->update([
                'discount_id' => $this->discountId,
                'nama' => $this->nama_costumer,
                'discount_value' => $discountAmount,
                'total' => $totalAfterDiscount,
                'total_profit' => $totalProfit,
            ]);

            DB::commit();

            $this->pesanan = [];
            $this->mejas_id = null;
            $this->nama_costumer = '';

            $this->dispatch('showToast', message: 'Pesanan berhasil disimpan.', type: 'success', title: 'Success');
            if ($this->metode_pembayaran) {
                return redirect()->route('struk.print', base64_encode($pesanan->id));
            }
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
        $harga = $item->h_promo == '0' ? $item->harga : $item->h_promo;
        if (! $item) {
            return;
        }

        if (isset($this->pesanan[$id])) {
            $this->pesanan[$id]['qty']++;
        } else {
            $this->pesanan[$id] = [
                'id' => $item->id,
                'nama_menu' => $item->nama_menu,
                'harga' => (int) $harga,
                'gambar' => $item->gambar,
                'qty' => 1,
            ];
        }
    }

    public function increment($id)
    {
        if ($this->status === 'selesai') return;
        $this->updateQty($id, 1);
    }

    public function decrement($id)
    {
        if ($this->status === 'selesai') return;
        $this->updateQty($id, -1);
    }

    private function updateQty($id, $delta)
    {
        if (! isset($this->pesanan[$id])) {
            return;
        }

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

    public $selectedCategoryId = null;

    public function filterByCategory($categoryId)
    {
        // dd($categoryId);
        $this->selectedCategoryId = $categoryId;
    }

    public function render()
    {
        $menus = Menu::where('is_active', 1)
            ->where('nama_menu', 'like', '%' . $this->search . '%')
            ->paginate($this->perPage);

        $discMessage = null;
        $discountValue = 0;

        // Hitung total awal
        $total = collect($this->pesanan)->sum(fn($p) => $p['harga'] * $p['qty']);

        // Ambil data diskon berdasarkan kode
        $disc = Discount::where('kode_diskon', $this->discount)
            ->where('is_active', true)
            ->whereDate('tanggal_mulai', '<=', now())
            ->whereDate('tanggal_akhir', '>=', now())
            ->first();



        if ($disc) {
            // Cek apakah limit penggunaan sudah habis
            if (! is_null($disc->limit) && ! is_null($disc->digunakan) && $disc->digunakan >= $disc->limit) {
                $discMessage = 'Diskon sudah mencapai batas penggunaan.';
            }
            // Cek apakah total memenuhi minimum transaksi
            elseif ($disc->minimum_transaksi && $total < $disc->minimum_transaksi) {
                $discMessage = 'Minimal transaksi untuk diskon ini adalah Rp ' . number_format($disc->minimum_transaksi, 0, ',', '.');
            } else {
                // ✅ Terapkan diskon
                if ($disc->jenis_diskon === 'persentase') {
                    $discountValue = $total * ($disc->nilai_diskon / 100);

                    // Jika ada batas maksimum diskon
                    if ($disc->maksimum_diskon && $discountValue > $disc->maksimum_diskon) {
                        $discountValue = $disc->maksimum_diskon;
                    }
                } elseif ($disc->jenis_diskon === 'nominal') {
                    $discountValue = $disc->nilai_diskon;
                }

                $discMessage = 'Diskon berhasil diterapkan.';
            }
            $this->discountId = $disc->id;

            $result = [
                'nama' => $disc->nama_diskon,
                'type' => $disc->jenis_diskon,
                'nilai' => $disc->nilai_diskon,
                'min' => $disc->minimum_transaksi,
                'max' => $disc->maksimum_diskon,
                'kode' => $disc->kode_diskon,
            ];
        } else {
            $result = null;
            if ($this->discount == '') {
                $discMessage = null;
            } else {
                $discMessage = 'Kode diskon tidak valid atau sudah tidak aktif.';
            }
        }

        $cekMember = Member::where('phone', $this->member)->first();
        if ($cekMember) {
            $memMessage = "Member Tersedia " . "(" . $cekMember->user->name . ")";
            $this->dispatch('showToast', message: "Member Tersedia " . "(" . $cekMember->user->name . ")", type: 'success', title: 'Success');
        } else {
            if ($this->member) {
                $memMessage = "Member Tidak Tersedia ";
            } else {
                $memMessage = "";
            }
        }
        // 08222323232
        $totalAfterDiscount = max(0, $total - $discountValue);

        $categories = Category::with(['menus' => function ($query) {
            $query->where('is_active', true);
        }])->get();
        return view('livewire.order.create-order', [
            'menus' => $menus,
            'orderId' => $this->orderId,
            'status' => $this->status,
            'disc' => $result,
            'discMessage' => $discMessage,
            'total' => $total,
            'categories' => $categories,
            'discountValue' => $discountValue,
            'totalAfterDiscount' => $totalAfterDiscount,
            'memMessage' => $memMessage ?? null
        ]);
    }
}
