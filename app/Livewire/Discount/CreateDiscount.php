<?php

namespace App\Livewire\Discount;

use Livewire\Component;
use App\Models\Discount;

class CreateDiscount extends Component
{
    public $discountId = null;
    public $button = "Simpan";
    public $title = "Tambah Diskon Baru";
    public $backUrl = "/discount";

    // Form fields
    public $nama_diskon, $jenis_diskon, $nilai_diskon, $minimum_transaksi, $limit, $type = 'general';
    public $maksimum_diskon, $kode_diskon, $tanggal_mulai, $tanggal_akhir;
    public $is_active = 1;
    public $member_only = false;
    public $scope = 'global';
    public $branch_id = null;
    public $price_tier_id = null;
    public $selectedItems = [];

    public $menus = [];
    public $categories = [];
    public $branches = [];
    public $priceTiers = [];

    protected $rules = [
        'nama_diskon' => 'required|string|max:255',
        'jenis_diskon' => 'required|in:persentase,nominal',
        'nilai_diskon' => 'required|numeric|min:0',
        'minimum_transaksi' => 'nullable|numeric|min:0',
        'maksimum_diskon' => 'nullable|numeric|min:0',
        'kode_diskon' => 'nullable|string|max:50',
        'tanggal_mulai' => 'nullable|date',
        'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal_mulai',
        'is_active' => 'required|boolean',
        'member_only' => 'required|boolean',
        'scope' => 'required|in:global,category,item',
        'branch_id' => 'nullable|exists:branches,id',
        'price_tier_id' => 'nullable|exists:price_tiers,id',
    ];

    public function mount($id = null)
    {
        $this->menus = \App\Models\Menu::where('is_active', 1)->get();
        $this->categories = \App\Models\Category::all();
        // Branch isolation: non-superadmin hanya melihat branch sendiri
        $this->branches = auth()->user()->hasRole('superadmin')
            ? \App\Models\Branch::all()
            : collect([auth()->user()->branch])->filter();
        $this->priceTiers = \App\Models\PriceTier::all();

        if ($id) {
            $this->discountId = $id;
            $this->button = "Update";
            $this->title = "Edit Diskon";
            $this->loadData();
        }
    }

    public function loadData()
    {
        $diskon = Discount::findOrFail(base64_decode($this->discountId));

        // Branch isolation: non-superadmin tidak boleh membuka discount
        // branch lain atau shared (branch_id NULL).
        abort_unless($diskon->isManageableBy(auth()->user()), 403, 'Anda tidak memiliki akses untuk mengedit diskon ini.');

        $this->discountId = $diskon->id;
        $this->nama_diskon = $diskon->nama_diskon;
        $this->jenis_diskon = $diskon->jenis_diskon;
        $this->nilai_diskon = $diskon->nilai_diskon;
        $this->minimum_transaksi = $diskon->minimum_transaksi;
        $this->maksimum_diskon = $diskon->maksimum_diskon;
        $this->kode_diskon = $diskon->kode_diskon;
        $this->tanggal_mulai = $diskon->tanggal_mulai;
        $this->tanggal_akhir = $diskon->tanggal_akhir;
        $this->limit = $diskon->limit;
        $this->is_active = $diskon->is_active;
        $this->member_only = (bool) $diskon->member_only;
        $this->type = $diskon->type;
        $this->scope = $diskon->scope;
        $this->branch_id = $diskon->branch_id;
        $this->price_tier_id = $diskon->price_tier_id;

        if ($diskon->scope !== 'global') {
            $this->selectedItems = $diskon->discountItems->map(fn($item) => $item->model_id)->toArray();
        }
    }

    public function updatedScope($value)
    {
        if ($value === 'item' || $value === 'category') {
            $this->dispatch('open-modal', name: 'scope-modal');
        }
    }

    public function simpan()
    {
        if (!auth()->user()->can('manage discount')) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola diskon.');
        }
        $this->validate();

        // Branch isolation: non-superadmin branch_id WAJIB dipaksa ke branch user.
        // Tidak percaya branch_id dari Livewire property/request.
        $user = auth()->user();

        // Non-superadmin tanpa branch tidak boleh membuat discount
        // (karena akan terpaksa menjadi shared discount).
        if (! $user->hasRole('superadmin') && ! $user->branch_id) {
            abort(403, 'Anda tidak memiliki cabang, tidak dapat membuat diskon.');
        }

        $branchId = $user->hasRole('superadmin')
            ? ($this->branch_id ?: null)
            : $user->branch_id;

        $discount = Discount::create([

            'nama_diskon' => $this->nama_diskon,
            'jenis_diskon' => $this->jenis_diskon,
            'nilai_diskon' => $this->nilai_diskon,
            'minimum_transaksi' => $this->minimum_transaksi,
            'maksimum_diskon' => $this->maksimum_diskon,
            'kode_diskon' => $this->kode_diskon,
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_akhir' => $this->tanggal_akhir,
            'limit' => $this->limit,
            'is_active' => $this->is_active,
            'member_only' => $this->member_only,
            'type' => $this->type,
            'scope' => $this->scope,
            'branch_id' => $branchId,
            'price_tier_id' => $this->price_tier_id ?: null,
        ]);

        if ($this->scope !== 'global' && !empty($this->selectedItems)) {
            $modelType = $this->scope === 'item' ? 'App\Models\Menu' : 'App\Models\Category';
            foreach ($this->selectedItems as $itemId) {
                \App\Models\DiscountItem::create([
                    'discount_id' => $discount->id,
                    'model_type' => $modelType,
                    'model_id' => $itemId,
                ]);
            }
        }

        $this->resetForm();
        $this->dispatch('showToast', message: 'Discount Berhasil Ditambah', type: 'success', title: 'Success');
    }

    public function update($id)
    {
        if (!auth()->user()->can('manage discount')) {
            abort(403, 'Anda tidak memiliki akses untuk mengelola diskon.');
        }
        // $this->validate();

        $diskon = Discount::findOrFail($id);

        // Branch isolation: non-superadmin tidak boleh update discount
        // branch lain atau shared (branch_id NULL).
        abort_unless($diskon->isManageableBy(auth()->user()), 403, 'Anda tidak memiliki akses untuk mengedit diskon ini.');

        // Non-superadmin tidak boleh mengubah branch_id: paksa tetap branch user.
        $branchId = auth()->user()->hasRole('superadmin')
            ? ($this->branch_id ?: null)
            : $diskon->branch_id;

        $diskon->update([
            'nama_diskon' => $this->nama_diskon,
            'jenis_diskon' => $this->jenis_diskon,
            'nilai_diskon' => $this->nilai_diskon,
            'minimum_transaksi' => $this->minimum_transaksi,
            'maksimum_diskon' => $this->maksimum_diskon,
            'kode_diskon' => $this->kode_diskon,
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_akhir' => $this->tanggal_akhir,
            'limit' => $this->limit,
            'is_active' => $this->is_active,
            'member_only' => $this->member_only,
            'type' => $this->type,
            'scope' => $this->scope,
            'branch_id' => $branchId,
            'price_tier_id' => $this->price_tier_id ?: null,
        ]);

        \App\Models\DiscountItem::where('discount_id', $diskon->id)->delete();
        if ($this->scope !== 'global' && !empty($this->selectedItems)) {
            $modelType = $this->scope === 'item' ? 'App\Models\Menu' : 'App\Models\Category';
            foreach ($this->selectedItems as $itemId) {
                \App\Models\DiscountItem::create([
                    'discount_id' => $diskon->id,
                    'model_type' => $modelType,
                    'model_id' => $itemId,
                ]);
            }
        }

        $this->dispatch('showToast', message: 'Discount Berhasil Diupdate', type: 'success', title: 'Success');
    }



    public function resetForm()
    {
        $this->discountId = null;
        $this->button = "Simpan";
        $this->nama_diskon = null;
        $this->jenis_diskon = null;
        $this->nilai_diskon = null;
        $this->minimum_transaksi = null;
        $this->maksimum_diskon = null;
        $this->kode_diskon = null;
        $this->tanggal_mulai = null;
        $this->tanggal_akhir = null;
        $this->is_active = 1;
        $this->member_only = false;
        $this->limit = null;
        $this->type = '';
        $this->scope = 'global';
        $this->branch_id = null;
        $this->price_tier_id = null;
        $this->selectedItems = [];
    }

    public function render()
    {
        return view('livewire.discount.create-discount', [
            'title' => $this->title,
            'backUrl' => $this->backUrl
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
