<?php

namespace App\Livewire\Admin\Shift;

use App\Models\Shift;
use App\Models\Branch;
use Livewire\Component;
use Livewire\WithPagination;

class ShiftManager extends Component
{
    use WithPagination;

    public $nama_shift, $jam_masuk, $jam_keluar, $branch_id;
    public $denda_telat = 20000;
    public $maksimal_telat_menit = 60;
    public $batas_awal_absen_menit = 60;
    
    public $shiftId;
    public $isEdit = false;

    protected $rules = [
        'nama_shift' => 'required|string|max:255',
        'jam_masuk' => 'required',
        'jam_keluar' => 'required',
        'branch_id' => 'nullable|exists:branches,id',
        'denda_telat' => 'required|numeric|min:0',
        'maksimal_telat_menit' => 'required|integer|min:0',
        'batas_awal_absen_menit' => 'required|integer|min:0',
    ];

    public function render()
    {
        $shifts = Shift::with('branch')->paginate(10);
        $branches = Branch::all();
        return view('livewire.admin.shift.shift-manager', compact('shifts', 'branches'))->layout('layouts.app');
    }

    public function resetFields()
    {
        $this->reset(['nama_shift', 'jam_masuk', 'jam_keluar', 'branch_id', 'denda_telat', 'maksimal_telat_menit', 'batas_awal_absen_menit', 'shiftId', 'isEdit']);
        $this->denda_telat = 20000;
        $this->maksimal_telat_menit = 60;
        $this->batas_awal_absen_menit = 60;
    }

    public function saveShift()
    {
        $this->validate();

        $data = [
            'nama_shift' => $this->nama_shift,
            'jam_masuk' => $this->jam_masuk,
            'jam_keluar' => $this->jam_keluar,
            'branch_id' => $this->branch_id ?: null,
            'denda_telat' => $this->denda_telat,
            'maksimal_telat_menit' => $this->maksimal_telat_menit,
            'batas_awal_absen_menit' => $this->batas_awal_absen_menit,
        ];

        if ($this->isEdit) {
            Shift::find($this->shiftId)->update($data);
            session()->flash('success', 'Shift berhasil diperbarui.');
        } else {
            Shift::create($data);
            session()->flash('success', 'Shift berhasil ditambahkan.');
        }

        $this->resetFields();
    }

    public function editShift($id)
    {
        $shift = Shift::findOrFail($id);
        $this->shiftId = $id;
        $this->nama_shift = $shift->nama_shift;
        $this->jam_masuk = $shift->jam_masuk;
        $this->jam_keluar = $shift->jam_keluar;
        $this->branch_id = $shift->branch_id;
        $this->denda_telat = $shift->denda_telat;
        $this->maksimal_telat_menit = $shift->maksimal_telat_menit;
        $this->batas_awal_absen_menit = $shift->batas_awal_absen_menit ?? 60;
        $this->isEdit = true;
    }

    public function deleteShift($id)
    {
        Shift::find($id)->delete();
        session()->flash('success', 'Shift berhasil dihapus.');
    }
}
