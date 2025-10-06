<?php

namespace App\Livewire\Meja;

use App\Models\Meja;
use Livewire\Component;
use Livewire\WithPagination;

class IndexMeja extends Component
{
    use WithPagination;

    // public $meja;
    public $nomeja;

    public $idmeja;

    public $isimeja;

    public $button = 'Simpan Meja';

    public function formreset()
    {
        $this->nomeja = '';
        $this->isimeja = '';
        $this->button = 'Simpan Meja';
    }

    public function delMeja($id){
        // dd(base64_decode($id));
        $meja = Meja::find(base64_decode($id));
        $meja->delete();
         $this->dispatch('showToast', message: 'Meja Berhasil Dihapus', type: 'danger', title: 'Success');
    }

    public function edit($id)
    {
        // dd($id);
        $meja = Meja::find($id);
        $this->nomeja = $meja->nama;
        $this->isimeja = $meja->kapasitas;
        $this->idmeja = $meja->id;
        $this->button = 'Update Meja';
    }

    public function simpan()
    {
        $meja = Meja::find($this->idmeja);

        if ($meja) {
            $meja->update([
                'nama' => $this->nomeja,
                'kapasitas' => $this->isimeja,
            ]);

            $this->dispatch('showToast', message: 'Meja Berhasil Diupdate', type: 'success', title: 'Success');
        } else {
            Meja::create([
                'nama' => $this->nomeja,
                'kapasitas' => $this->isimeja,
            ]);

            $this->dispatch('showToast', message: 'Meja Berhasil Ditambah', type: 'success', title: 'Success');
        }

        $this->formreset();
    }

    public function render()
    {
        $meja = Meja::all();

        return view('livewire.meja.index-meja', ['meja' => $meja]);
    }
}
