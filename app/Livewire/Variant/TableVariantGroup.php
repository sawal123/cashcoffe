<?php

namespace App\Livewire\Variant;

use App\Models\VariantGroup;
use App\Models\VariantOption;
use Livewire\Component;
use Livewire\WithPagination;

class TableVariantGroup extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $isEdit = false;
    public $variantGroupId;

    // Form fields
    public $nama_group;
    public $selection_type = 'single';
    public $is_required = true;
    public $options = []; // Array of options
    public $salesChannels = [];
    public $tiers = [];

    protected $rules = [
        'nama_group' => 'required|min:2',
        'selection_type' => 'required|in:single,multiple',
        'is_required' => 'boolean',
        'options.*.nama_opsi' => 'required|min:1',
    ];

    public function mount()
    {
        $this->salesChannels = \App\Models\SalesChannel::where('is_active', true)->get();
        $this->tiers = \App\Models\PriceTier::all();
        $this->addOption();
    }

    public function addOption()
    {
        $matrixPrices = [];
        foreach ($this->tiers as $tier) {
            foreach ($this->salesChannels as $channel) {
                $matrixPrices['tier_' . $tier->id]['channel_' . $channel->id] = 0;
            }
        }
        $this->options[] = [
            'id' => null, 
            'nama_opsi' => '', 
            'prices' => $matrixPrices
        ];
    }

    public function removeOption($index)
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $group = VariantGroup::with(['options.variantPrices'])->findOrFail($id);
        $this->variantGroupId = $id;
        $this->nama_group = $group->nama_group;
        $this->selection_type = $group->selection_type;
        $this->is_required = (bool) $group->is_required;
        
        $this->options = [];
        foreach ($group->options as $opt) {
            $matrixPrices = [];
            foreach ($this->tiers as $tier) {
                foreach ($this->salesChannels as $channel) {
                    $vp = $opt->variantPrices->where('price_tier_id', $tier->id)->where('sales_channel_id', $channel->id)->first();
                    $matrixPrices['tier_' . $tier->id]['channel_' . $channel->id] = $vp ? (int) $vp->extra_price : 0;
                }
            }
            $this->options[] = [
                'id' => $opt->id,
                'nama_opsi' => $opt->nama_opsi,
                'prices' => $matrixPrices
            ];
        }

        $this->isEdit = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $group = VariantGroup::updateOrCreate(
            ['id' => $this->variantGroupId],
            [
                'nama_group' => $this->nama_group,
                'selection_type' => $this->selection_type,
                'is_required' => $this->is_required,
            ]
        );

        // Hapus opsi yang tidak ada di form (untuk Edit)
        if ($this->isEdit) {
            $existingIds = collect($this->options)->pluck('id')->filter()->toArray();
            $group->options()->whereNotIn('id', $existingIds)->delete();
        }

        // Sync Options & Prices
        foreach ($this->options as $optData) {
            $option = VariantOption::updateOrCreate(
                ['id' => $optData['id'] ?? null],
                [
                    'variant_group_id' => $group->id,
                    'nama_opsi' => $optData['nama_opsi'],
                    'extra_price' => 0, // Legacy support if needed, or default
                ]
            );

            // Sync VariantPrices
            if (isset($optData['prices'])) {
                foreach ($this->tiers as $tier) {
                    foreach ($this->salesChannels as $channel) {
                        $priceValue = $optData['prices']['tier_' . $tier->id]['channel_' . $channel->id] ?? 0;
                        \App\Models\VariantPrice::updateOrCreate(
                            [
                                'variant_option_id' => $option->id,
                                'price_tier_id' => $tier->id,
                                'sales_channel_id' => $channel->id,
                            ],
                            [
                                'extra_price' => (int) round($priceValue),
                            ]
                        );
                    }
                }
            }
        }

        $this->showModal = false;
        $this->dispatch('showToast', message: 'Grup varian berhasil disimpan', type: 'success');
        $this->resetForm();
    }

    public function delete($id)
    {
        VariantGroup::findOrFail($id)->delete();
        $this->dispatch('showToast', message: 'Grup varian berhasil dihapus', type: 'success');
    }

    private function resetForm()
    {
        $this->variantGroupId = null;
        $this->nama_group = '';
        $this->selection_type = 'single';
        $this->is_required = true;
        $this->options = [];
        $this->addOption();
    }

    public function render()
    {
        return view('livewire.variant.table-variant-group', [
            'groups' => VariantGroup::where('nama_group', 'like', '%' . $this->search . '%')
                ->withCount('options')
                ->with(['options' => fn($q) => $q->withCount('ingredients')])
                ->latest()
                ->paginate(10),
            'title' => 'Daftar Grup Varian Produk'
        ])->layout('layouts.app', ['title' => 'Grup Varian']);
    }
}
