<?php

namespace App\Livewire\Tier;

use App\Models\PriceTier;
use Livewire\Component;

class Index extends Component
{
    public $nama_tier, $is_active = true, $tier_id;
    public $isEdit = false;

    protected $rules = [
        'nama_tier' => 'required|string|max:255',
        'is_active' => 'boolean',
    ];

    public function render()
    {
        $tiers = PriceTier::all();
        return view('livewire.tier.index', compact('tiers'));
    }

    public function create()
    {
        $this->resetFields();
        $this->isEdit = false;
        $this->dispatch('open-modal', name: 'modal-tier');
    }

    public function store()
    {
        $this->validate();

        PriceTier::create([
            'nama_tier' => $this->nama_tier,
            'is_active' => $this->is_active,
        ]);

        $this->dispatch('showToast', message: 'Tier Harga Berhasil Ditambahkan', type: 'success', title: 'Success');
        $this->closeModal();
    }

    public function edit($id)
    {
        $tier = PriceTier::findOrFail($id);
        $this->tier_id = $tier->id;
        $this->nama_tier = $tier->nama_tier;
        $this->is_active = $tier->is_active;

        $this->isEdit = true;
        $this->dispatch('open-modal', name: 'modal-tier');
    }

    public function update()
    {
        $this->validate();

        $tier = PriceTier::findOrFail($this->tier_id);
        $tier->update([
            'nama_tier' => $this->nama_tier,
            'is_active' => $this->is_active,
        ]);

        $this->dispatch('showToast', message: 'Tier Harga Berhasil Diperbarui', type: 'success', title: 'Success');
        $this->closeModal();
    }

    public function toggleStatus($id)
    {
        $tier = PriceTier::findOrFail($id);
        $tier->is_active = !$tier->is_active;
        $tier->save();
        $this->dispatch('showToast', message: 'Status Tier Berhasil Diubah', type: 'success', title: 'Success');
    }

    public function closeModal()
    {
        $this->dispatch('close-modal', name: 'modal-tier');
        $this->resetFields();
    }

    public function resetFields()
    {
        $this->nama_tier = '';
        $this->is_active = true;
        $this->tier_id = null;
    }
}
