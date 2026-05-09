<?php

namespace App\Livewire\Asset;

use App\Models\Asset;
use App\Models\Branch;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    protected $paginationTheme = 'tailwind';

    // Form properties
    public $assetId;
    public $branch_id;
    public $kode_aset;
    public $nama_aset;
    public $qty = 1;
    public $kategori;
    public $kondisi = 'Baik';
    public $tanggal_pembelian;
    public $harga_beli;
    public $keterangan;

    public $isEdit = false;

    protected $rules = [
        'branch_id' => 'required|exists:branches,id',
        'kode_aset' => 'required|unique:assets,kode_aset',
        'nama_aset' => 'required|string|max:255',
        'qty' => 'required|integer|min:1',
        'kategori' => 'required|in:Elektronik,Furniture,Peralatan Dapur,IT',
        'kondisi' => 'required|in:Baik,Rusak Ringan,Rusak Berat,Dalam Perbaikan',
        'tanggal_pembelian' => 'required|date',
        'harga_beli' => 'required|numeric|min:0',
        'keterangan' => 'nullable|string',
    ];

    public function mount()
    {
        if (Auth::user()->hasRole('manager')) {
            $this->branch_id = Auth::user()->branch_id;
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function resetForm()
    {
        $this->assetId = null;
        $this->kode_aset = '';
        $this->nama_aset = '';
        $this->qty = 1;
        $this->kategori = '';
        $this->kondisi = 'Baik';
        $this->tanggal_pembelian = '';
        $this->harga_beli = '';
        $this->keterangan = '';
        $this->isEdit = false;

        if (Auth::user()->hasRole('superadmin')) {
            $this->branch_id = null;
        } else {
            $this->branch_id = Auth::user()->branch_id;
        }
        
        $this->resetErrorBag();
    }

    public function openModal()
    {
        $this->resetForm();
        $this->dispatch('open-modal', name: 'asset-modal');
    }

    public function editAsset($id)
    {
        $this->resetForm();
        $asset = Asset::findOrFail($id);
        
        $this->assetId = $asset->id;
        $this->branch_id = $asset->branch_id;
        $this->kode_aset = $asset->kode_aset;
        $this->nama_aset = $asset->nama_aset;
        $this->qty = $asset->qty;
        $this->kategori = $asset->kategori;
        $this->kondisi = $asset->kondisi;
        $this->tanggal_pembelian = $asset->tanggal_pembelian->format('Y-m-d');
        $this->harga_beli = $asset->harga_beli;
        $this->keterangan = $asset->keterangan;
        
        $this->isEdit = true;
        $this->dispatch('open-modal', name: 'asset-modal');
    }

    public function saveAsset()
    {
        $user = Auth::user();
        
        if ($this->isEdit) {
            $asset = Asset::findOrFail($this->assetId);
            
            // Authorization
            if ($user->hasRole('manager')) {
                // Manager only allowed to update status (kondisi) and keterangan
                $asset->update([
                    'kondisi' => $this->kondisi,
                    'keterangan' => $this->keterangan,
                ]);
                $this->dispatch('showToast', message: 'Kondisi aset berhasil diperbarui.', type: 'success', title: 'Success');
            } else if ($user->hasRole('superadmin')) {
                $data = $this->validate([
                    'branch_id' => 'required|exists:branches,id',
                    'kode_aset' => 'required|unique:assets,kode_aset,' . $this->assetId,
                    'nama_aset' => 'required|string|max:255',
                    'qty' => 'required|integer|min:1',
                    'kategori' => 'required|in:Elektronik,Furniture,Peralatan Dapur,IT',
                    'kondisi' => 'required|in:Baik,Rusak Ringan,Rusak Berat,Dalam Perbaikan',
                    'tanggal_pembelian' => 'required|date',
                    'harga_beli' => 'required|numeric|min:0',
                    'keterangan' => 'nullable|string',
                ]);
                $asset->update($data);
                $this->dispatch('showToast', message: 'Aset berhasil diperbarui.', type: 'success', title: 'Success');
            }
        } else {
            // Only Superadmin can Add
            if (!$user->hasRole('superadmin')) {
                $this->dispatch('showToast', message: 'Anda tidak memiliki akses untuk menambah aset.', type: 'error', title: 'Error');
                return;
            }

            $data = $this->validate();
            Asset::create($data);
            $this->dispatch('showToast', message: 'Aset baru berhasil ditambahkan.', type: 'success', title: 'Success');
        }

        $this->dispatch('close-modal', name: 'asset-modal');
        $this->resetForm();
    }

    public function deleteAsset($id)
    {
        if (!Auth::user()->hasRole('superadmin')) {
            $this->dispatch('showToast', message: 'Hanya Superadmin yang dapat menghapus aset.', type: 'error', title: 'Error');
            return;
        }

        Asset::findOrFail($id)->delete();
        $this->dispatch('showToast', message: 'Aset berhasil dihapus.', type: 'success', title: 'Success');
    }

    public function render()
    {
        $user = Auth::user();
        
        $query = Asset::with('branch')
            ->when($user->hasRole('manager'), function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            ->when($this->search, function ($q) {
                $q->where(function($sub) {
                    $sub->where('nama_aset', 'like', '%' . $this->search . '%')
                        ->orWhere('kode_aset', 'like', '%' . $this->search . '%')
                        ->orWhere('kategori', 'like', '%' . $this->search . '%');
                });
            });

        return view('livewire.asset.index', [
            'assets' => $query->latest()->paginate($this->perPage),
            'branches' => Branch::all(),
            'title' => 'Manajemen Aset & Inventaris'
        ])->layout('layouts.app', ['title' => 'Manajemen Aset']);
    }
}
