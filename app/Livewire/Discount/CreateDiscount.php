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
        'scope' => 'required|in:global,category,item',
        'branch_id' => 'nullable|exists:branches,id',
        'price_tier_id' => 'nullable|exists:price_tiers,id',
    ];

    public function mount($id = null)
    {
        $this->menus = \App\Models\Menu::where('is_active', 1)->get();
        $this->categories = \App\Models\Category::all();
        $this->branches = \App\Models\Branch::all();
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
        $this->validate();

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
            'type' => $this->type,
            'scope' => $this->scope,
            'branch_id' => $this->branch_id ?: null,
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
        // $this->validate();

        $diskon = Discount::findOrFail($id);

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
            'type' => $this->type,
            'scope' => $this->scope,
            'branch_id' => $this->branch_id ?: null,
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
