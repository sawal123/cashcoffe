<?php

namespace App\Livewire\Pengeluaran;

use App\Models\Pengeluaran;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    // Properti input form
    public $tanggal_pengeluaran;

    public $kategori;

    public $title;

    public $satuan;

    public $total;

    public $metode_pembayaran;

    public $catatan;

    public $bukti;

    public $pengeluaranId = null;

    public function simpan()
    {
        $this->validate([
            'tanggal_pengeluaran' => 'required|date',
            'kategori' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'satuan' => 'nullable|string|max:50',
            'total' => 'required|numeric|min:0',
            'metode_pembayaran' => 'nullable|string|max:100',
            'catatan' => 'nullable|string',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // max 2MB
        ]);
        // dd($this->bukti);
        // Upload bukti jika ada
        $buktiPath = null;
        if ($this->bukti) {
            $buktiPath = $this->bukti->store('bukti_pengeluaran', 'public');
        }
        

        // Simpan data ke database
        Pengeluaran::create([
            'user_id' => Auth::id(),
            'tanggal_pengeluaran' => $this->tanggal_pengeluaran,
            'kategori' => $this->kategori,
            'title' => $this->title,
            'satuan' => $this->satuan,
            'total' => $this->total,
            'metode_pembayaran' => $this->metode_pembayaran,
            'catatan' => $this->catatan,
            'bukti' => $buktiPath,
        ]);

        // Reset form setelah simpan
        $this->reset(['tanggal_pengeluaran', 'kategori', 'title', 'satuan', 'total', 'metode_pembayaran', 'catatan', 'bukti']);

        $this->dispatch('showToast', type: 'success', message: 'Data pengeluaran berhasil disimpan!');
    }

    public function render()
    {
        return view('livewire.pengeluaran.create');
    }
}
