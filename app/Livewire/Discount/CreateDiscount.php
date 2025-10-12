<?php

namespace App\Livewire\Discount;

use Livewire\Component;
use App\Models\Discount;

class CreateDiscount extends Component
{
    public $discountId = null;
    public $button = "Simpan";

    // Form fields
    public $nama_diskon, $jenis_diskon, $nilai_diskon, $minimum_transaksi, $limit;
    public $maksimum_diskon, $kode_diskon, $tanggal_mulai, $tanggal_akhir;
    public $is_active = 1;

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
    ];

    public function mount($id = null)
    {
        // dd(base64_decode($this->discountId));
        if ($this->discountId) {
            // $this->discountId = $id;
            $this->button = "Update";
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
    }

    public function simpan()
    {
        $this->validate();

        Discount::create([

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
        ]);

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
        ]);

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
    }

    public function render()
    {
        return view('livewire.discount.create-discount');
    }
}
