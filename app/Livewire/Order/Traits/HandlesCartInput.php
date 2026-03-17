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
        $item = Menu::find($id);
        $harga = $item->h_promo == '0' ? $item->harga : $item->h_promo;

        if (! $item) return;

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
        if (! isset($this->pesanan[$id])) return;

        $newQty = $this->pesanan[$id]['qty'] + $delta;

        if ($newQty > 0) {
            $this->pesanan[$id]['qty'] = $newQty;
        } else {
            unset($this->pesanan[$id]);
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

        // Cari user admin (Sesuaikan dengan nama kolom role di tabel users Anda)
        // Asumsinya Anda punya kolom role = 'admin' atau is_admin = 1
        $admins = User::role('admin')->get();
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


        $adminIds = \App\Models\User::role('admin')->pluck('id')->map(fn($id) => (string)$id)->toArray();

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
