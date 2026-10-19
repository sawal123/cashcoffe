<?php

namespace App\Livewire\Gudang;

use App\Models\Gudang;
use App\Services\GudangStockService;
use Livewire\Component;
use App\Models\GudangRiwayat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreateGudang extends Component
{
    public $gudangId = null;
    public $riwayatId = null;
    public $branch_id = null;
    public $branchLocked = false;
    public $nama_bahan, $jumlah_masuk, $satuan, $harga_satuan, $minimum_stok, $keterangan;
    public $tipe = 'masuk'; // default
    public $submit = 'simpan';
    public $button = 'Simpan';
    public $daftarBahan = [];
    public $title = 'Tambah Stok Gudang';
    public $backUrl = '/gudang';

    private function authorizeBranch(): ?int
    {
        $user = auth()->user();
        abort_unless($user && $user->hasAnyRole(['admin', 'superadmin']), 403);
        if ($user->hasRole('superadmin')) {
            return null;
        }
        abort_if($user->branch_id === null, 403, 'Admin harus memiliki cabang.');
        return (int) $user->branch_id;
    }

    private function selectedBranchId(): int
    {
        $adminBranchId = $this->authorizeBranch();
        if ($adminBranchId !== null) {
            return $adminBranchId; // Ignore manipulated Livewire branch field.
        }
        $this->validate(['branch_id' => 'required|integer|exists:branches,id']);
        return (int) $this->branch_id;
    }

    private function refreshSuggestions(): void
    {
        $branchId = auth()->user()->hasRole('superadmin')
            ? $this->branch_id : auth()->user()->branch_id;
        $this->daftarBahan = $branchId
            ? Gudang::where('branch_id', $branchId)->orderBy('nama_bahan')->pluck('nama_bahan')->toArray()
            : [];
    }

    public function updatedBranchId(): void
    {
        $this->authorizeBranch();
        $this->refreshSuggestions();
    }

    public function mount($gudangId = null, $riwayatId = null)
    {
        $this->branch_id = $this->authorizeBranch();
        if ($riwayatId) {
            $riwayat = GudangRiwayat::visibleTo(auth()->user())
                ->with('gudang')->findOrFail(base64_decode($riwayatId, true));
            $this->riwayatId = $riwayatId;
            $this->branch_id = $riwayat->gudang->branch_id;
            $this->branchLocked = true;
            $this->title = 'Edit Riwayat Gudang';
            $this->nama_bahan = $riwayat->gudang->nama_bahan;
            $this->jumlah_masuk = $riwayat->jumlah;
            $this->satuan = $riwayat->gudang->satuan;
            $this->harga_satuan = $riwayat->harga_satuan;
            $this->minimum_stok = $riwayat->gudang->minimum_stok;
            $this->keterangan = $riwayat->keterangan;
            $this->tipe = $riwayat->tipe;
        } elseif ($gudangId) {
            $gudang = Gudang::visibleTo(auth()->user())
                ->findOrFail(base64_decode($gudangId, true));
            $this->gudangId = $gudangId;
            $this->branch_id = $gudang->branch_id;
            $this->branchLocked = $gudang->branch_id !== null;
            $this->title = 'Edit Bahan Gudang';
            $this->nama_bahan = $gudang->nama_bahan;
            $this->satuan = $gudang->satuan;
            $this->harga_satuan = $gudang->harga_satuan;
            $this->minimum_stok = $gudang->minimum_stok;
            $this->keterangan = $gudang->keterangan;
        }
        $this->refreshSuggestions();
    }

    public function searchBahan($query)
    {
        $this->authorizeBranch();
        $branchId = auth()->user()->hasRole('superadmin')
            ? $this->branch_id : auth()->user()->branch_id;
        $this->daftarBahan = $branchId
            ? Gudang::where('branch_id', $branchId)
                ->where('nama_bahan', 'like', "%{$query}%")
                ->limit(5)->pluck('nama_bahan')->toArray()
            : [];
    }

    public function simpan()
    {
        $branchId = $this->selectedBranchId();
        $stockService = new GudangStockService;
        $this->validate([
            'nama_bahan' => 'required|string|max:255',
            'jumlah_masuk' => 'required|numeric|min:1',
            'satuan' => 'required|string',
            'harga_satuan' => 'nullable|numeric|min:0',
            'minimum_stok' => 'nullable|numeric|min:0',
            'tipe' => 'required|in:masuk,keluar',
        ]);

        DB::beginTransaction();

        try {
            // Cari bahan berdasarkan nama
            $gudang = Gudang::where('branch_id', $branchId)
                ->where('nama_bahan', $this->nama_bahan)->lockForUpdate()->first();

            if (! $gudang && $this->tipe === 'keluar') {
                throw new \InvalidArgumentException('Tidak dapat mengeluarkan bahan yang belum ada di cabang ini.');
            }

            if (! $gudang) {
                // Barang baru
                $stokSebelum = 0;
                $stokSesudah = $this->jumlah_masuk;

                $gudang = Gudang::create([
                    'branch_id' => $branchId,
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
                    $hargaLama = (float) ($gudang->harga_satuan ?? 0);
                    $hargaBaru = (float) ($this->harga_satuan ?? $hargaLama);
                    $total_nilai_lama = $stokSebelum * $hargaLama;
                    $total_nilai_baru = $this->jumlah_masuk * $hargaBaru;

                    $stokSesudah = $stokSebelum + $this->jumlah_masuk;
                    $harga_rata2 = $stokSebelum > 0 && $stokSesudah > 0
                        ? ($total_nilai_lama + $total_nilai_baru) / $stokSesudah
                        : $hargaBaru;

                    $gudang->update([
                        'stok' => $stokSesudah,
                        'harga_satuan' => $harga_rata2,
                    ]);
                } else {
                    // Jika keluar
                    $stokSesudah = $stokSebelum - $this->jumlah_masuk;
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
                'total_harga' => $this->jumlah_masuk * ($this->harga_satuan ?? 0),
                'keterangan' => $this->keterangan ?? ucfirst($this->tipe) . ' stok',
                'user_id' => Auth::user()->id,
            ]);
            $stockService->rebuild($gudang);
            // dd($this->jumlah_masuk * $this->harga_satuan);

            DB::commit();
            $this->refreshSuggestions();
            $this->dispatch('showToast', type: 'success', message: 'Data gudang berhasil disimpan!');
            $this->reset(['nama_bahan', 'jumlah_masuk', 'satuan', 'harga_satuan', 'minimum_stok', 'keterangan']);
            $this->tipe = 'masuk';

            $this->refreshSuggestions();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showToast', type: 'error', message: 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function update()
    {
        $this->authorizeBranch();
        $stockService = new GudangStockService;
        $this->validate([
            'nama_bahan' => 'required|string|max:255',
            'jumlah_masuk' => 'required|numeric|min:1',
            'satuan' => 'required|string',
            'harga_satuan' => 'nullable|numeric|min:0',
            'minimum_stok' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $riwayat = GudangRiwayat::visibleTo(auth()->user())
                ->whereKey(base64_decode($this->riwayatId, true))
                ->firstOrFail();
            $gudang = Gudang::visibleTo(auth()->user())
                ->lockForUpdate()
                ->findOrFail($riwayat->gudang_id);
            $riwayat = GudangRiwayat::whereKey($riwayat->id)
                ->where('gudang_id', $gudang->id)
                ->lockForUpdate()
                ->firstOrFail();

            $jumlahLama = $riwayat->jumlah;
            $jumlahBaru = $this->jumlah_masuk;
            $selisih = $jumlahBaru - $jumlahLama;

            if ($riwayat->tipe === 'masuk' && $this->harga_satuan !== null) {
                $stokSetelahEdit = (float) $gudang->stok + $selisih;
                $stokTotal = $stokSetelahEdit + $jumlahBaru;
                $hargaLama = (float) ($gudang->harga_satuan ?? 0);
                $totalNilaiLama = $stokSetelahEdit * $hargaLama;
                $totalNilaiBaru = $jumlahBaru * $this->harga_satuan;

                $gudang->harga_satuan = $stokSetelahEdit > 0 && $stokTotal > 0
                    ? ($totalNilaiLama + $totalNilaiBaru) / $stokTotal
                    : $this->harga_satuan;
            }

            $riwayat->update([
                'jumlah' => $jumlahBaru,
                'harga_satuan' => $this->harga_satuan,
                'total_harga' => $jumlahBaru * ($this->harga_satuan ?? 0),
                'keterangan' => $this->keterangan ?? ucfirst($riwayat->tipe) . ' stok',
            ]);
            $stockService->rebuild($gudang);

            DB::commit();

            $this->dispatch('showToast', type: 'success', message: 'Data gudang berhasil diperbarui!');
            $this->reset(['riwayatId', 'nama_bahan', 'jumlah_masuk', 'satuan', 'harga_satuan', 'minimum_stok', 'keterangan', 'tipe']);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showToast', type: 'error', message: 'Gagal memperbarui: ' . $e->getMessage());
        }
    }


    public function updateBahan()
    {
        $this->authorizeBranch();
        $branchId = $this->selectedBranchId();
        $this->validate([
            'nama_bahan' => 'required|string|max:255',
            'satuan' => 'required|string',
            'harga_satuan' => 'nullable|numeric|min:0',
            'minimum_stok' => 'nullable|numeric|min:0',
        ]);
        $gudang = Gudang::visibleTo(auth()->user())
            ->findOrFail(base64_decode($this->gudangId, true));
        // A legacy warehouse entry may be assigned once; existing ownership is immutable.
        abort_if($gudang->branch_id !== null && (int) $gudang->branch_id !== $branchId, 403);
        $gudang->update([
            'branch_id' => $branchId,
            'nama_bahan' => $this->nama_bahan,
            'satuan' => $this->satuan,
            'harga_satuan' => $this->harga_satuan,
            'minimum_stok' => $this->minimum_stok ?? 0,
            'keterangan' => $this->keterangan,
        ]);
        $this->branchLocked = true;
        $this->dispatch('showToast', type: 'success', message: 'Data bahan gudang berhasil diperbarui!');
    }

    public function render()
    {
        $this->authorizeBranch();
        return view('livewire.gudang.create-gudang', [
            'branches' => auth()->user()->hasRole('superadmin')
                ? \App\Models\Branch::orderBy('nama_cabang')->get()
                : collect(),
            'title' => $this->title,
            'backUrl' => $this->backUrl
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
