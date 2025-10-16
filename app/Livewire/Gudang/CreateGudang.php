<?php

namespace App\Livewire\Gudang;

use App\Models\Gudang;
use Livewire\Component;
use App\Models\GudangRiwayat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreateGudang extends Component
{
    public $gudangId = null;
    public $nama_bahan, $jumlah_masuk, $satuan, $harga_satuan, $minimum_stok, $keterangan;
    public $tipe = 'masuk'; // default
    public $submit = 'simpan';
    public $button = 'Simpan';
    public $daftarBahan = [];

    public function mount()
    {
        $this->daftarBahan = Gudang::pluck('nama_bahan')->toArray();
        $gudang = GudangRiwayat::find(\base64_decode($this->gudangId));

        // dd($gudang->gudang->nama_bahan);
        if (!$gudang) {
            $this->dispatch('showToast', [
                'type' => 'error',
                'message' => 'Data bahan tidak ditemukan.'
            ]);
            return;
        }
        $this->gudangId = $gudang->id;
        $this->nama_bahan = $gudang->gudang->nama_bahan;
        $this->jumlah_masuk = $gudang->jumlah;
        $this->satuan = $gudang->gudang->satuan;
        $this->harga_satuan = $gudang->harga_satuan;
        $this->minimum_stok = $gudang->minimum_stok;
        $this->keterangan = $gudang->keterangan;
        $this->tipe = $gudang->tipe;
    }


    public function searchBahan($query)
    {
        $this->daftarBahan = Gudang::where('nama_bahan', 'like', "%{$query}%")
            ->limit(5)
            ->pluck('nama_bahan')
            ->toArray();
    }
    public function simpan()
    {
        $this->validate([
            'nama_bahan' => 'required|string|max:255',
            'jumlah_masuk' => 'required|numeric|min:1',
            'satuan' => 'required|string',
            'harga_satuan' => 'nullable|numeric|min:0',
            'minimum_stok' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Cari bahan berdasarkan nama
            $gudang = Gudang::where('nama_bahan', $this->nama_bahan)->first();

            if (! $gudang) {
                // Barang baru
                $stokSebelum = 0;
                $stokSesudah = $this->jumlah_masuk;

                $gudang = Gudang::create([
                    'nama_bahan' => $this->nama_bahan,
                    'stok' => $stokSesudah,
                    'satuan' => $this->satuan,
                    'harga_satuan' => $this->harga_satuan ?? 0,
                    'minimum_stok' => $this->minimum_stok ?? 0,
                    'keterangan' => $this->keterangan ?? '-',
                ]);
            } else {
                // Barang sudah ada
                $stokSebelum = $gudang->stok;

                if ($this->tipe === 'masuk') {
                    // Hitung rata-rata tertimbang
                    $total_nilai_lama = $stokSebelum * $gudang->harga_satuan;
                    $total_nilai_baru = $this->jumlah_masuk * $this->harga_satuan;

                    $stokSesudah = $stokSebelum + $this->jumlah_masuk;
                    $harga_rata2 = $stokSesudah > 0
                        ? ($total_nilai_lama + $total_nilai_baru) / $stokSesudah
                        : $gudang->harga_satuan;

                    $gudang->update([
                        'stok' => $stokSesudah,
                        'harga_satuan' => $harga_rata2,
                    ]);
                } else {
                    // Jika keluar
                    $stokSesudah = max(0, $stokSebelum - $this->jumlah_masuk);
                    $gudang->update(['stok' => $stokSesudah]);
                }
            }


            GudangRiwayat::create([
                'gudang_id' => $gudang->id,
                'tipe' => $this->tipe,
                'jumlah' => $this->jumlah_masuk,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'harga_satuan' => $this->harga_satuan,
                'total_harga' => $this->jumlah_masuk * $this->harga_satuan,
                'keterangan' => $this->keterangan ?? ucfirst($this->tipe) . ' stok',
                'user_id' => Auth::user()->id,
            ]);
            // dd($this->jumlah_masuk * $this->harga_satuan);

            DB::commit();
            $this->daftarBahan = Gudang::pluck('nama_bahan')->toArray();
            $this->dispatch('showToast', type: 'success', message: 'Data gudang berhasil disimpan!');
            $this->reset(['nama_bahan', 'jumlah_masuk', 'satuan', 'harga_satuan', 'minimum_stok', 'keterangan', 'tipe']);

            $this->daftarBahan = Gudang::pluck('nama_bahan')->toArray();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showToast', type: 'error', message: 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function update()
    {
        $this->validate([
            'nama_bahan' => 'required|string|max:255',
            'jumlah_masuk' => 'required|numeric|min:1',
            'satuan' => 'required|string',
            'harga_satuan' => 'nullable|numeric|min:0',
            'minimum_stok' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $riwayat = GudangRiwayat::findOrFail($this->gudangId);
            $gudang = $riwayat->gudang;

            // Ambil stok awal sebelum ada perubahan
            $stokSebelum = $gudang->stok;

            // Ambil jumlah lama dari riwayat sebelum diubah
            $jumlahLama = $riwayat->jumlah;
            $jumlahBaru = $this->jumlah_masuk;

            // Hitung selisih jumlah
            $selisih = $jumlahBaru - $jumlahLama;

            if ($riwayat->tipe === 'masuk') {
                // Update stok gudang: tambah atau kurangi berdasarkan selisih
                $gudang->stok += $selisih;

                // Update harga rata-rata baru kalau harga berubah
                if ($this->harga_satuan !== null) {
                    $total_nilai_lama = $gudang->stok * $gudang->harga_satuan;
                    $total_nilai_baru = $jumlahBaru * $this->harga_satuan;
                    $stokTotal = $gudang->stok + $jumlahBaru;

                    $harga_rata2 = $stokTotal > 0
                        ? ($total_nilai_lama + $total_nilai_baru) / $stokTotal
                        : $gudang->harga_satuan;

                    $gudang->harga_satuan = $harga_rata2;
                }
            } else {
                // tipe keluar
                $gudang->stok -= $selisih; // selisih negatif artinya stok bertambah (revisi)
                if ($gudang->stok < 0) {
                    $gudang->stok = 0;
                }
            }

            // Update Gudang
            $gudang->save();

            // Update Riwayat
            $riwayat->update([
                'jumlah' => $jumlahBaru,
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $gudang->stok,
                'harga_satuan' => $this->harga_satuan,
                'total_harga' => $jumlahBaru * $this->harga_satuan,
                'keterangan' => $this->keterangan ?? ucfirst($riwayat->tipe) . ' stok',
            ]);

            DB::commit();

            $this->dispatch('showToast', type: 'success', message: 'Data gudang berhasil diperbarui!');
            $this->reset(['gudangId', 'nama_bahan', 'jumlah_masuk', 'satuan', 'harga_satuan', 'minimum_stok', 'keterangan', 'tipe']);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showToast', type: 'error', message: 'Gagal memperbarui: ' . $e->getMessage());
        }
    }


    public function render()
    {
        return view('livewire.gudang.create-gudang');
    }
}
