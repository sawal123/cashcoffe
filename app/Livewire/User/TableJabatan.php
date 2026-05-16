<?php

namespace App\Livewire\User;

use App\Models\Jabatan;
use Livewire\Component;
use Livewire\WithPagination;

class TableJabatan extends Component
{
    use WithPagination;

    public $search = '';
    public $nama_jabatan;
    public $jabatanId;
    public $isEdit = false;

    protected $rules = [
        'nama_jabatan' => 'required|string|max:255|unique:jabatans,nama_jabatan',
    ];

    public function resetFields()
    {
        $this->nama_jabatan = '';
        $this->jabatanId = null;
        $this->isEdit = false;
    }

    public function store()
    {
        $this->validate();

        Jabatan::create([
            'nama_jabatan' => $this->nama_jabatan
        ]);

        $this->resetFields();
        $this->dispatch('close-modal', name: 'modal-jabatan');
        $this->dispatch('showToast', type: 'success', message: 'Jabatan berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $jabatan = Jabatan::findOrFail($id);
        $this->jabatanId = $jabatan->id;
        $this->nama_jabatan = $jabatan->nama_jabatan;
        $this->isEdit = true;
        $this->dispatch('open-modal', name: 'modal-jabatan');
    }

    public function update()
    {
        $this->validate([
            'nama_jabatan' => 'required|string|max:255|unique:jabatans,nama_jabatan,' . $this->jabatanId,
        ]);

        $jabatan = Jabatan::findOrFail($this->jabatanId);
        $jabatan->update([
            'nama_jabatan' => $this->nama_jabatan
        ]);

        $this->resetFields();
        $this->dispatch('close-modal', name: 'modal-jabatan');
        $this->dispatch('showToast', type: 'success', message: 'Jabatan berhasil diperbarui.');
    }

    public function delete($id)
    {
        $jabatan = Jabatan::findOrFail($id);

        // Check if used by users
        if ($jabatan->users()->count() > 0) {
            $this->dispatch('showToast', type: 'error', message: 'Gagal! Jabatan ini masih digunakan oleh ' . $jabatan->users()->count() . ' user.');
            return;
        }

        $jabatan->delete();
        $this->dispatch('showToast', type: 'success', message: 'Jabatan berhasil dihapus.');
    }

    public function render()
    {
        $jabatans = Jabatan::where('nama_jabatan', 'like', '%' . $this->search . '%')
            ->withCount('users')
            ->latest()
            ->paginate(10);

        return view('livewire.user.table-jabatan', [
            'jabatans' => $jabatans
        ])->layout('layouts.app', ['title' => 'Kelola Jabatan']);
    }
}
