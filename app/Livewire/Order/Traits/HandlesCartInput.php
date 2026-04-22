<?php

namespace App\Livewire\Order\Traits;

use App\Models\DiscountApproval;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

trait HandlesCartInput
{
    // public function removeDiscount()
    // {
    //     $this->discount = null;
    //     $this->discount_id = null;
    //     $this->discount_value = 0;
    // }

    // Reset halaman setiap kali search berubah
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function addPesanan($id)
    {
        $item = Menu::with(['variantGroups.options'])->find($id);
        if (!$item) return;

        // Mendapatkan Harga berdasarkan Tier Cabang User
        $user = Auth::user();
        $priceTierId = $user->branch ? $user->branch->price_tier_id : null;

        $tieredPrice = \App\Models\MenuPrice::where('menu_id', $id)
            ->where('price_tier_id', $priceTierId)
            ->first();

        $harga = ($tieredPrice) 
            ? (($tieredPrice->h_promo > 0) ? $tieredPrice->h_promo : $tieredPrice->harga)
            : (($item->h_promo > 0) ? $item->h_promo : $item->harga);

        // Jika menu punya varian, buka modal penyesuaian
        if ($item->variantGroups->count() > 0) {
            $this->selectedMenuForVariant = [
                'id' => $item->id,
                'nama_menu' => $item->nama_menu,
                'harga_base' => (int) $harga,
                'gambar' => $item->gambar,
                'groups' => $item->variantGroups
            ];
            $this->tempSelectedOptions = [];
            $this->totalExtraPrice = 0;
            $this->showVariantModal = true;
            $this->dispatch('open-modal', name: 'variant-modal');
            $this->dispatch('showToast', message: 'Pilih varian untuk ' . $item->nama_menu, type: 'info', title: 'Penyesuaian Menu');
            return;
        }

        // Jika tidak ada varian, tambahkan langsung seperti biasa
        $cartKey = $id; 
        if (isset($this->pesanan[$cartKey])) {
            $this->pesanan[$cartKey]['qty']++;
        } else {
            $this->pesanan[$cartKey] = [
                'id' => $item->id,
                'nama_menu' => $item->nama_menu,
                'harga' => (int) $harga,
                'gambar' => $item->gambar,
                'qty' => 1,
                'selected_options' => [],
                'display_options' => []
            ];
        }
    }

    public function selectOption($groupId, $optionId, $type = 'single')
    {
        if ($type === 'single') {
            $this->tempSelectedOptions[$groupId] = [$optionId];
        } else {
            // Multiple Selection (Add-on)
            if (!isset($this->tempSelectedOptions[$groupId])) {
                $this->tempSelectedOptions[$groupId] = [];
            }

            if (in_array($optionId, $this->tempSelectedOptions[$groupId])) {
                $this->tempSelectedOptions[$groupId] = array_diff($this->tempSelectedOptions[$groupId], [$optionId]);
            } else {
                $this->tempSelectedOptions[$groupId][] = $optionId;
            }
        }

        $this->recalculateExtraPrice();
    }

    private function recalculateExtraPrice()
    {
        $total = 0;
        foreach ($this->tempSelectedOptions as $groupId => $optionIds) {
            foreach ($optionIds as $optId) {
                $option = \App\Models\VariantOption::find($optId);
                if ($option) {
                    $total += (int) $option->extra_price;
                }
            }
        }
        $this->totalExtraPrice = $total;
    }

    public function confirmVariant()
    {
        if (!$this->selectedMenuForVariant) return;

        $menu = $this->selectedMenuForVariant;
        
        // Validasi: Pastikan semua group yang required sudah dipilih
        foreach ($menu['groups'] as $group) {
            if ($group->is_required && (!isset($this->tempSelectedOptions[$group->id]) || empty($this->tempSelectedOptions[$group->id]))) {
                $this->dispatch('showToast', message: 'Pilih ' . $group->nama_group . ' terlebih dahulu', type: 'warning', title: 'Required');
                return;
            }
        }

        $allOptionIds = [];
        foreach ($this->tempSelectedOptions as $groupId => $optionIds) {
            foreach ($optionIds as $optId) {
                $allOptionIds[] = $optId;
            }
        }
        sort($allOptionIds);

        // Buat slug unik agar Americano Hot & Americano Ice tidak duplikat
        $optionSlug = count($allOptionIds) > 0 ? '_' . md5(json_encode($allOptionIds)) : '';
        $cartKey = $menu['id'] . $optionSlug;

        $totalHarga = $menu['harga_base'] + $this->totalExtraPrice;

        if (isset($this->pesanan[$cartKey])) {
            $this->pesanan[$cartKey]['qty']++;
        } else {
            // Ambil nama-nama opsi untuk ditampilkan di UI keranjang
            $displayOptions = \App\Models\VariantOption::whereIn('id', $allOptionIds)->pluck('nama_opsi')->toArray();

            $this->pesanan[$cartKey] = [
                'id' => $menu['id'],
                'nama_menu' => $menu['nama_menu'],
                'harga' => (int) $totalHarga,
                'gambar' => $menu['gambar'],
                'qty' => 1,
                'selected_options' => $allOptionIds,
                'display_options' => $displayOptions
            ];
        }

        // Close Modal & Reset
        $this->showVariantModal = false;
        $this->selectedMenuForVariant = null;
        $this->tempSelectedOptions = [];
        $this->totalExtraPrice = 0;
        $this->dispatch('close-modal', name: 'variant-modal');
        $this->dispatch('showToast', message: $menu['nama_menu'] . ' berhasil ditambahkan ke pesanan', type: 'success', title: 'Berhasil');
    }

    public function increment($key)
    {
        if ($this->status === 'selesai') return;
        $this->updateQty($key, 1);
    }

    public function decrement($key)
    {
        if ($this->status === 'selesai') return;
        $this->updateQty($key, -1);
    }

    private function updateQty($key, $delta)
    {
        if (! isset($this->pesanan[$key])) return;

        $newQty = $this->pesanan[$key]['qty'] + $delta;

        if ($newQty > 0) {
            $this->pesanan[$key]['qty'] = $newQty;
        } else {
            unset($this->pesanan[$key]);
        }
    }

    public function filterByCategory($categoryId)
    {
        $this->selectedCategoryId = $categoryId;
    }

    public function updatedMetodePembayaran()
    {
        if (! $this->isCash) {
            $this->uang_tunai = null;
            $this->kembalian = 0;
        }
    }

    public function updatedUangTunai($value)
    {
        if ($this->isCash && is_numeric($value)) {
            $this->kembalian = max(0, $value - $this->total1);
        } else {
            $this->kembalian = 0;
        }
    }

    public function getTotalProperty()
    {
        return collect($this->pesanan)->sum(fn($p) => $p['harga'] * $p['qty']);
    }

    public function getIsCashProperty()
    {
        return $this->metode_pembayaran === 'tunai';
    }


    public function verifyDiscount()
    {
        // Validasi input tidak boleh kosong
        $this->validate([
            'adminPassword' => 'required'
        ]);

        // Cari user admin atau superadmin (Menggunakan whereHas agar tidak error jika role tidak ada)
        $admins = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['superadmin', 'admin']);
        })->get();
        $isPasswordCorrect = false;
        // Cek kecocokan password
        foreach ($admins as $admin) {
            if (Hash::check($this->adminPassword, $admin->password)) {
                $isPasswordCorrect = true;
                break; // Jika ketemu satu yang cocok, hentikan pencarian
            }
        }
        if ($isPasswordCorrect) {
            $this->isDiscountVerified = true;
            $this->adminPassword = ''; // Kosongkan kembali demi keamanan

            // Tutup modal dan beri pesan sukses
            $this->dispatch('close-modal', name: 'verify-discount-modal');
            $this->dispatch('showToast', message: 'Verifikasi berhasil. Diskon diterapkan.', type: 'success');
        } else {
            $this->addError('adminPassword', 'Password Admin salah!');
        }
    }

    public function updatedDiscount()
    {
        // Reset status verifikasi menjadi false. 
        // Jika kodenya private, kasir wajib masukin password lagi.
        $this->isDiscountVerified = false;
    }

    // Jangan lupa reset status verifikasi saat diskon dihapus
    public function removeDiscount()
    {
        $this->discount = null;
        $this->discount_id = null;
        $this->discount_value = 0;
        $this->isDiscountVerified = false; // Reset status ini
    }

    // Fungsi untuk mengirim notif/request ke Admin
    public function requestAdminApproval()
    {
        // Cari ID diskon berdasarkan kode yang diketik kasir
        $disc = \App\Models\Discount::where('kode_diskon', $this->discount)->first();

        if (!$disc) {
            $this->addError('adminPassword', 'Kode diskon tidak ditemukan.');
            return;
        }

        // --- CONTOH LOGIC PENYIMPANAN KE DATABASE ---
        // Anda perlu membuat tabel (misal: 'discount_approvals') untuk menampung request ini.
        // Berisi kolom: id, kasir_id, discount_id, status ('pending', 'approved', 'rejected')

        $approval = DiscountApproval::create([
            'kasir_id' => Auth::user()->id, // ID kasir yang sedang login
            'discount_id' => $disc->id,
            'status' => 'pending'
        ]);

        $this->approvalRequestId = $approval->id;


        // Simulasi jika berhasil dikirim:
        $this->isWaitingApproval = true; // Mengubah UI jadi mode Loading


        // Cari user admin atau superadmin untuk mengirim notifikasi
        $adminIds = \App\Models\User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['superadmin', 'admin']);
        })->pluck('id')->map(fn($id) => (string)$id)->toArray();

        if (count($adminIds) > 0) {
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Basic ' . env('ONESIGNAL_REST_API_KEY'),
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ])->post('https://onesignal.com/api/v1/notifications', [
                    'app_id' => env('ONESIGNAL_APP_ID'),

                    // 👉 UBAH BAGIAN INI SEMENTARA:
                    'included_segments' => ['Total Subscriptions'], // Ini akan mengirim ke SEMUA perangkat

                    // (HAPUS ATAU JADIKAN KOMENTAR BAGIAN INI DULU)
                    // 'target_channel' => 'push',
                    // 'include_aliases' => [
                    //     'external_id' => $adminIds 
                    // ],

                    'headings' => ['en' => 'Temuan Space'],
                    'contents' => ['en' => 'Confirmastion Discount Request from ' . Auth::user()->name],
                ]);

                \Illuminate\Support\Facades\Log::info('OneSignal Response: ' . $response->body());
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('OneSignal Error: ' . $e->getMessage());
            }
        }
        $this->dispatch('showToast', message: 'Notifikasi berhasil dikirim ke Admin.', type: 'info');
    }

    // Fungsi ini akan dipanggil otomatis setiap 2 detik oleh wire:poll.2s di blade
    public function checkApprovalStatus()
    {
        if (!$this->isWaitingApproval || !$this->approvalRequestId) return;

        // Cek ke database apakah admin sudah klik "YA" (status menjadi 'approved')

        $approval = DiscountApproval::find($this->approvalRequestId);

        if ($approval && $approval->status === 'approved') {
            // JIKA DI-ACC ADMIN:
            $this->isDiscountVerified = true;
            $this->isWaitingApproval = false;
            $this->approvalRequestId = null;

            $this->dispatch('close-modal', name: 'verify-discount-modal');
            $this->dispatch('showToast', message: 'Diskon Disetujui Admin!', type: 'success');
        } elseif ($approval && $approval->status === 'rejected') {
            // JIKA DITOLAK ADMIN:
            $this->isWaitingApproval = false;
            $this->approvalRequestId = null;
            $this->addError('adminPassword', 'Permintaan diskon DITOLAK oleh Admin.');
        }
    }
}
