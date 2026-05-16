<?php

namespace App\Livewire\Branch;

use App\Models\Branch;
use Livewire\Component;

class Index extends Component
{
    public $nama_cabang, $kode_cabang, $alamat, $no_telp, $is_active = true, $branch_id, $price_tier_id;
    public $latitude, $longitude, $radius = 50;
    public $isEdit = false;

    protected $rules = [
        'nama_cabang' => 'required|string|max:255',
        'kode_cabang' => 'required|string|max:50|unique:branches,kode_cabang',
        'alamat' => 'nullable|string',
        'no_telp' => 'nullable|string',
        'is_active' => 'boolean',
        'price_tier_id' => 'required|exists:price_tiers,id',
        'latitude' => 'nullable|string|max:255',
        'longitude' => 'nullable|string|max:255',
        'radius' => 'nullable|integer|min:1',
    ];

    public function render()
    {
        $branches = Branch::with('priceTier')->get();
        $tiers = \App\Models\PriceTier::all();
        return view('livewire.branch.index', [
            'branches' => $branches,
            'tiers' => $tiers,
            'title' => 'Manajemen Cabang & Outlet'
        ])->layout('layouts.app', ['title' => 'Cabang']);
    }

    public function create()
    {
        $this->resetFields();
        $this->isEdit = false;
        $this->dispatch('open-modal', name: 'modal-branch');
    }

    public function store()
    {
        $this->validate();

        Branch::create([
            'nama_cabang' => $this->nama_cabang,
            'kode_cabang' => $this->kode_cabang,
            'alamat' => $this->alamat,
            'no_telp' => $this->no_telp,
            'is_active' => $this->is_active,
            'price_tier_id' => $this->price_tier_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius' => $this->radius ?? 50,
        ]);

        $this->dispatch('showToast', message: 'Cabang Berhasil Ditambahkan', type: 'success', title: 'Success');
        $this->closeModal();
    }

    public function edit($id)
    {
        $branch = Branch::findOrFail($id);
        $this->branch_id = $branch->id;
        $this->nama_cabang = $branch->nama_cabang;
        $this->kode_cabang = $branch->kode_cabang;
        $this->alamat = $branch->alamat;
        $this->no_telp = $branch->no_telp;
        $this->is_active = $branch->is_active;
        $this->price_tier_id = $branch->price_tier_id;
        $this->latitude = $branch->latitude;
        $this->longitude = $branch->longitude;
        $this->radius = $branch->radius;

        $this->isEdit = true;
        $this->dispatch('open-modal', name: 'modal-branch');
    }

    public function update()
    {
        $this->validate([
            'nama_cabang' => 'required|string|max:255',
            'kode_cabang' => 'required|string|max:50|unique:branches,kode_cabang,' . $this->branch_id,
            'alamat' => 'nullable|string',
            'no_telp' => 'nullable|string',
            'is_active' => 'boolean',
            'price_tier_id' => 'required|exists:price_tiers,id',
            'latitude' => 'nullable|string|max:255',
            'longitude' => 'nullable|string|max:255',
            'radius' => 'nullable|integer|min:1',
        ]);

        $branch = Branch::findOrFail($this->branch_id);
        $branch->update([
            'nama_cabang' => $this->nama_cabang,
            'kode_cabang' => $this->kode_cabang,
            'alamat' => $this->alamat,
            'no_telp' => $this->no_telp,
            'is_active' => $this->is_active,
            'price_tier_id' => $this->price_tier_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius' => $this->radius ?? 50,
        ]);

        $this->dispatch('showToast', message: 'Cabang Berhasil Diperbarui', type: 'success', title: 'Success');
        $this->closeModal();
    }

    public function toggleStatus($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->is_active = !$branch->is_active;
        $branch->save();
        $this->dispatch('showToast', message: 'Status Cabang Berhasil Diubah', type: 'success', title: 'Success');
    }

    public function closeModal()
    {
        $this->dispatch('close-modal', name: 'modal-branch');
        $this->resetFields();
    }

    public function resetFields()
    {
        $this->nama_cabang = '';
        $this->kode_cabang = '';
        $this->alamat = '';
        $this->no_telp = '';
        $this->is_active = true;
        $this->branch_id = null;
        $this->price_tier_id = '';
        $this->latitude = '';
        $this->longitude = '';
        $this->radius = 50;
    }
}
