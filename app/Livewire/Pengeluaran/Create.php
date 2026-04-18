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

    public function mount($pengeluaranId = null)
    {
        if ($pengeluaranId) {
            $this->pengeluaranId = $pengeluaranId;
            $pengeluaran = Pengeluaran::findOrFail($pengeluaranId);
            $this->tanggal_pengeluaran = $pengeluaran->tanggal_pengeluaran;
            $this->kategori = $pengeluaran->kategori;
            $this->title = $pengeluaran->title;
            $this->satuan = $pengeluaran->satuan;
            $this->total = $pengeluaran->total;
            $this->metode_pembayaran = $pengeluaran->metode_pembayaran;
            $this->catatan = $pengeluaran->catatan;
            $this->bukti = $pengeluaran->bukti;
        } else {
            $this->tanggal_pengeluaran = now()->format('Y-m-d');
        }
    }

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

        $buktiPath = null;
        if ($this->bukti && !is_string($this->bukti)) {
            $buktiPath = $this->bukti->store('bukti_pengeluaran', 'public');
        }

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
            'branch_id' => Auth::user()->branch_id, // Added branch_id if applicable
        ]);

        $this->reset(['tanggal_pengeluaran', 'kategori', 'title', 'satuan', 'total', 'metode_pembayaran', 'catatan', 'bukti']);
        $this->tanggal_pengeluaran = now()->format('Y-m-d');

        $this->dispatch('showToast', type: 'success', message: 'Data pengeluaran berhasil disimpan!');
    }

    public function update($id)
    {
        $this->validate([
            'tanggal_pengeluaran' => 'required|date',
            'kategori' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'satuan' => 'nullable|string|max:50',
            'total' => 'required|numeric|min:0',
            'metode_pembayaran' => 'nullable|string|max:100',
            'catatan' => 'nullable|string',
            'bukti' => 'nullable|max:2048', // max 2MB, can be string (old path) or file
        ]);

        $pengeluaran = Pengeluaran::findOrFail($id);

        $buktiPath = $pengeluaran->bukti;
        if ($this->bukti && !is_string($this->bukti)) {
            // Delete old file if exists
            if ($pengeluaran->bukti) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($pengeluaran->bukti);
            }
            $buktiPath = $this->bukti->store('bukti_pengeluaran', 'public');
        }

        $pengeluaran->update([
            'tanggal_pengeluaran' => $this->tanggal_pengeluaran,
            'kategori' => $this->kategori,
            'title' => $this->title,
            'satuan' => $this->satuan,
            'total' => $this->total,
            'metode_pembayaran' => $this->metode_pembayaran,
            'catatan' => $this->catatan,
            'bukti' => $buktiPath,
        ]);

        $this->dispatch('showToast', type: 'success', message: 'Data pengeluaran berhasil diperbarui!');
    }

    public function setMetode($metode)
    {
        $this->metode_pembayaran = $metode;
    }

    public function getRecentPengeluaransProperty()
    {
        return Pengeluaran::latest()->limit(2)->get();
    }

    public function getBudgetSummaryProperty()
    {
        // Static mockup for now as requested by the UI design
        return [
            'sisa' => 12450000,
            'total' => 25000000,
            'persen' => 49.8
        ];
    }

    public function render()
    {
        return view('livewire.pengeluaran.create');
    }
}
