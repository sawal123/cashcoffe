<?php

namespace App\Livewire\Stock;

use App\Models\Branch;
use App\Models\Ingredients;
use App\Models\RiwayatStock;
use App\Models\SatuanBahan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class StockDapurCreate extends Component
{
    public $nama_bahan;
    public $stok;
    public $satuan_id;

    public $newSatuan;
    public $hpp;

    public $satuans;
    public $branch_id;

    #[Locked]
    public ?int $ingredient_id = null;

    public bool $isEdit = false;
    public $qty, $keterangan, $current_stok, $current_satuan;
    public $editSatuanId, $editSatuanNama;

    public function mount($stockId = null)
    {
        $this->loadSatuan();

        if ($stockId !== null && $stockId !== '') {
            $decoded = base64_decode($stockId, true);

            if ($decoded === false || !ctype_digit((string) $decoded) || (int) $decoded <= 0) {
                abort(404, 'Data bahan baku tidak ditemukan.');
            }

            $bahan = Ingredients::find((int) $decoded);

            if (! $bahan) {
                abort(404, 'Data bahan baku tidak ditemukan.');
            }

            $this->ingredient_id = (int) $bahan->id;
            $this->isEdit = true;
            $this->nama_bahan = $bahan->nama_bahan;
            $this->satuan_id = $bahan->satuan_id;
            $this->stok = (float) $bahan->stok == intval($bahan->stok) ? intval($bahan->stok) : (float) $bahan->stok;
            $this->hpp = $bahan->hpp !== null ? ((float) $bahan->hpp == intval($bahan->hpp) ? intval($bahan->hpp) : (float) $bahan->hpp) : null;
        }
    }


    public function loadSatuan()
    {
        $this->satuans = SatuanBahan::orderBy('nama_satuan')->get();
    }

    public function simpan()
    {
        $user = auth()->user();
        $isSuperadminWithoutBranch = $user && $user->hasRole('superadmin') && $user->branch_id === null;

        $rules = [
            'nama_bahan' => 'required',
            'stok' => 'required|numeric|min:0',
            'satuan_id' => 'required|exists:satuan_bahans,id',
            'hpp' => 'nullable|numeric|min:0',
        ];

        if ($isSuperadminWithoutBranch) {
            $rules['branch_id'] = [
                'required',
                Rule::exists('branches', 'id')->where('is_active', true),
            ];
        }

        $this->validate($rules, [
            'branch_id.required' => 'Cabang wajib dipilih.',
            'branch_id.exists' => 'Cabang yang dipilih tidak valid atau tidak aktif.',
        ]);

        if ($user && $user->branch_id !== null) {
            $resolvedBranchId = (int) $user->branch_id;
        } elseif ($isSuperadminWithoutBranch) {
            $resolvedBranchId = (int) $this->branch_id;
        } else {
            abort(403, 'User tidak memiliki cabang yang valid.');
        }

        if (! $resolvedBranchId) {
            abort(422, 'Cabang tidak valid.');
        }

        DB::transaction(function () use ($resolvedBranchId) {
            $ingredient = Ingredients::create([
                'nama_bahan' => $this->nama_bahan,
                'stok' => $this->stok,
                'hpp' => $this->hpp ?: 0,
                'satuan_id' => $this->satuan_id,
                'branch_id' => $resolvedBranchId,
            ]);

            RiwayatStock::create([
                'ingredient_id' => $ingredient->id,
                'branch_id' => $ingredient->branch_id,
                'kode' => strtoupper('IN-' . Str::random(6)),
                'qty' => $this->stok,
                'keterangan' => 'Stok awal',
                'tipe' => 'in',
            ]);
        });

        $this->reset(['nama_bahan', 'stok', 'satuan_id', 'hpp', 'branch_id']);

        $this->dispatch('showToast', type: 'success', message: 'Bahan berhasil disimpan!');
    }

    public function update()
    {
        if (! $this->ingredient_id) {
            abort(404, 'Data bahan baku tidak ditemukan.');
        }

        $this->validate([
            'nama_bahan' => 'required',
            'stok' => 'required|numeric|min:0',
            'satuan_id' => 'required|exists:satuan_bahans,id',
            'hpp' => 'nullable|numeric|min:0',
        ]);

        $bahan = Ingredients::find($this->ingredient_id);

        if (! $bahan) {
            abort(404, 'Data bahan baku tidak ditemukan.');
        }

        $bahan->update([
            'nama_bahan' => $this->nama_bahan,
            'stok' => $this->stok,
            'hpp' => $this->hpp ?: 0,
            'satuan_id' => $this->satuan_id,
        ]);

        $this->dispatch('showToast', type: 'success', message: 'Bahan berhasil diupdate!');
    }

    public function saveSatuan()
    {
        $this->validate([
            'newSatuan' => 'required|string|min:2'
        ]);

        SatuanBahan::create([
            'nama_satuan' => $this->newSatuan
        ]);

        $this->reset('newSatuan');
        $this->loadSatuan();

        $this->dispatch('showToast', type: 'success', message: 'Satuan berhasil ditambahkan!');
    }

    public function deleteSatuan($id)
    {
        $satuan = SatuanBahan::find($id);

        if (!$satuan) {
            return;
        }

        // Check if the unit is used in any ingredients (including soft-deleted ones)
        if ($satuan->ingredients()->withTrashed()->exists()) {
            $this->dispatch('showToast', type: 'error', message: 'Satuan tidak bisa dihapus karena sedang digunakan oleh bahan dapur!');
            return;
        }

        $satuan->delete();
        $this->loadSatuan();

        $this->dispatch('showToast', type: 'success', message: 'Satuan berhasil dihapus!');
    }

    public function editSatuan($id)
    {
        $satuan = SatuanBahan::findOrFail($id);
        $this->editSatuanId = $satuan->id;
        $this->editSatuanNama = $satuan->nama_satuan;

        $this->dispatch('open-modal', name: 'edit-satuan');
    }

    public function updateSatuan()
    {
        $this->validate([
            'editSatuanNama' => 'required|string|min:2'
        ]);

        $satuan = SatuanBahan::findOrFail($this->editSatuanId);
        $satuan->update([
            'nama_satuan' => $this->editSatuanNama
        ]);

        $this->loadSatuan();
        $this->dispatch('close-modal', name: 'edit-satuan');
        $this->dispatch('showToast', type: 'success', message: 'Satuan berhasil diupdate!');
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperadminWithoutBranch = $user && $user->hasRole('superadmin') && $user->branch_id === null;

        $branches = [];
        if (! $this->isEdit && $isSuperadminWithoutBranch) {
            $branches = Branch::where('is_active', true)->orderBy('nama_cabang')->get();
        }

        $title = $this->isEdit ? 'Edit Bahan Dapur' : 'Tambah Bahan Dapur';
        return view('livewire.stock.stock-dapur-create', [
            'title' => $title,
            'backUrl' => '/stock-dapur',
            'isEdit' => $this->isEdit,
            'isSuperadminWithoutBranch' => $isSuperadminWithoutBranch,
            'branches' => $branches,
        ])->layout('layouts.app', ['title' => $title]);
    }
}
