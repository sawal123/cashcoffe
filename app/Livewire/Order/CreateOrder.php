<?php

namespace App\Livewire\Order;

use App\Livewire\Order\Traits\HandlesCartInput;
use App\Livewire\Order\Traits\HandlesOrderSubmit;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Meja;
use App\Models\Member;
use App\Models\Menu;
// Import Trait yang baru kita buat
use Livewire\Component;
use Livewire\WithPagination;

class CreateOrder extends Component
{
    // Panggil Trait di sini
    use HandlesCartInput, HandlesOrderSubmit;

    use WithPagination;

    public $adminPassword = ''; // Menyimpan inputan password admin di modal

    public $isDiscountVerified = false; // Status apakah diskon private sudah di-acc

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

    public $discount_id;

    public $discount_value;

    public $perPage = 12;

    public $metode_pembayaran = null;

    public $status = null;

    public $uang_tunai = null;

    public $kembalian = 0;

    public $lastPesananId;

    public $lastKodePesanan;

    public $lastTotalPesanan;

    public $selectedCategoryId = null;

    public $total1;

    public $isWaitingApproval = false; // Status apakah sedang menunggu ACC admin

    public $approvalRequestId = null; // Menyimpan ID request yang dikirim ke tabel

    // Properti baru untuk Varian
    public $showVariantModal = false;

    public $selectedMenuForVariant = null;

    public $tempSelectedOptions = []; // [group_id => [option_ids]]

    public $totalExtraPrice = 0;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        if ($this->orderId) {
            $this->submit = 'updateOrder';
            $this->teks = 'Update';
            $this->editOrder(base64_decode($this->orderId));
        }
        $this->mejas = Meja::all();
    }

    public function render()
    {
        $menus = Menu::where('is_active', 1)
            ->where('nama_menu', 'like', '%'.$this->search.'%')
            ->paginate($this->perPage);

        $discMessage = null;
        $discountValue = 0;
        $result = null; // 1. Deklarasikan nilai default di awal agar tidak error

        // Hitung total awal
        $total = collect($this->pesanan)->sum(fn ($p) => $p['harga'] * $p['qty']);

        // Ambil data diskon berdasarkan kode
        $disc = Discount::where('kode_diskon', $this->discount)
            ->where('is_active', true)
            ->whereDate('tanggal_mulai', '<=', now())
            ->whereDate('tanggal_akhir', '>=', now())
            ->first();

        if ($disc) {
            // 2. Isi variabel $result di sini, agar view selalu tahu tipe diskonnya (private/general)
            $result = [
                'nama' => $disc->nama_diskon,
                'type' => $disc->type,
                'jenis' => $disc->jenis_diskon,
                'nilai' => $disc->nilai_diskon,
                'min' => $disc->minimum_transaksi,
                'max' => $disc->maksimum_diskon,
                'kode' => $disc->kode_diskon,
            ];

            // 3. Cek apakah private dan belum diverifikasi
            if ($disc->type === 'private' && ! $this->isDiscountVerified) {
                $discMessage = 'Diskon private. Membutuhkan PIN/Password Admin.';
                $discountValue = 0; // Tahan diskon di angka 0

                // Jangan set $this->discountId dulu sampai diverifikasi
                $this->discountId = null;
                $this->discount_id = null;
            } else {
                // 4. JIKA GENERAL ATAU SUDAH DIVERIFIKASI, terapkan nilai diskon
                if (! is_null($disc->limit) && ! is_null($disc->digunakan) && $disc->digunakan >= $disc->limit) {
                    $discMessage = 'Diskon sudah mencapai batas penggunaan.';
                } elseif ($disc->minimum_transaksi && $total < $disc->minimum_transaksi) {
                    $discMessage = 'Minimal transaksi untuk diskon ini adalah Rp '.number_format($disc->minimum_transaksi, 0, ',', '.');
                } else {
                    if ($disc->jenis_diskon === 'persentase') {
                        $discountValue = $total * ($disc->nilai_diskon / 100);
                        if ($disc->maksimum_diskon && $discountValue > $disc->maksimum_diskon) {
                            $discountValue = $disc->maksimum_diskon;
                        }
                    } elseif ($disc->jenis_diskon === 'nominal') {
                        $discountValue = $disc->nilai_diskon;
                    }
                    $discMessage = 'Diskon berhasil diterapkan.';

                    // Set ID diskon karena sudah valid & bisa digunakan
                    $this->discountId = $disc->id;
                    $this->discount_id = $disc->id;
                }
            }
        } else {
            $result = null;
            if ($this->discount == '') {
                $discMessage = null;
            } else {
                $discMessage = 'Kode diskon tidak valid atau sudah tidak aktif.';
            }
            $this->isDiscountVerified = false; // Reset status verifikasi jika kode salah/dihapus
        }

        // ... sisa kode render ...

        $cekMember = Member::where('phone', $this->member)->first();
        if ($cekMember) {
            $memMessage = 'Member Tersedia ('.$cekMember->user->name.')';
            $this->dispatch('showToast', message: $memMessage, type: 'success', title: 'Success');
        } else {
            $memMessage = $this->member ? 'Member Tidak Tersedia ' : '';
        }

        $totalAfterDiscount = max(0, $total - $discountValue);
        $this->total1 = $totalAfterDiscount;

        $user = auth()->user();
        $priceTierId = $user->branch ? $user->branch->price_tier_id : null;

        $categories = Category::with([
            'menus' => function ($query) use ($priceTierId, $user) {
                $query->where('is_active', true)
                    ->where('nama_menu', 'like', '%'.$this->search.'%')
                    // Syarat: Hanya menu yang sudah diset harganya di Tier Cabang ini (Dipilih Pusat)
                    ->whereHas('menuPrices', function ($q) use ($priceTierId) {
                        $q->where('price_tier_id', $priceTierId);
                    })
                    // Syarat Tambahan: Belum dinonaktifkan oleh Cabang
                    ->where(function ($q) use ($user) {
                        $q->whereNotExists(function ($sub) use ($user) {
                            $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                                ->from('branch_menu')
                                ->whereColumn('branch_menu.menu_id', 'menus.id')
                                ->where('branch_id', $user->branch_id)
                                ->where('is_available', false);
                        });
                    })
                    ->with('ingredients');
            },
        ])->get();

        $this->kembalian = $this->isCash ? $this->uang_tunai - $totalAfterDiscount : 0;

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
            'memMessage' => $memMessage ?? null,
            'kembalian' => $this->kembalian,
        ]);
    }
}
