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
    public $options = [];

    protected $rules = [
        'nama_group' => 'required|min:2',
        'selection_type' => 'required|in:single,multiple',
        'is_required' => 'boolean',
        'options.*.nama_opsi' => 'required|min:1',
        'options.*.extra_price' => 'required|numeric|min:0',
    ];

    public function mount()
    {
        $this->addOption();
    }

    public function addOption()
    {
        $this->options[] = ['id' => null, 'nama_opsi' => '', 'extra_price' => 0];
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
        $group = VariantGroup::with('options')->findOrFail($id);
        $this->variantGroupId = $id;
        $this->nama_group = $group->nama_group;
        $this->selection_type = $group->selection_type;
        $this->is_required = (bool) $group->is_required;
        
        $this->options = $group->options->map(function($opt) {
            return [
                'id' => $opt->id,
                'nama_opsi' => $opt->nama_opsi,
                'extra_price' => $opt->extra_price
            ];
        })->toArray();

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

        // Sync Options
        foreach ($this->options as $optData) {
            VariantOption::updateOrCreate(
                ['id' => $optData['id'] ?? null],
                [
                    'variant_group_id' => $group->id,
                    'nama_opsi' => $optData['nama_opsi'],
                    'extra_price' => $optData['extra_price'],
                ]
            );
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
                ->latest()
                ->paginate(10)
        ]);
    }
}
