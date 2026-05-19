<?php

namespace App\Livewire\PaymentMethod;

use App\Models\PaymentMethod;
use Illuminate\Support\Str;
use Livewire\Component;

class Index extends Component
{
    public $nama_metode, $kode_metode, $is_active = true, $payment_method_id;
    public $isEdit = false;

    protected $rules = [
        'nama_metode' => 'required|string|max:255',
        'kode_metode' => 'nullable|string|max:255',
        'is_active' => 'boolean',
    ];

    public function render()
    {
        $methods = PaymentMethod::all();
        return view('livewire.payment-method.index', [
            'methods' => $methods,
            'title' => 'Daftar Metode Pembayaran'
        ])->layout('layouts.app', ['title' => 'Metode Pembayaran']);
    }

    public function create()
    {
        $this->resetFields();
        $this->isEdit = false;
        $this->dispatch('open-modal', name: 'modal-payment-method');
    }

    public function store()
    {
        $this->validate();

        $kode = $this->kode_metode ?: Str::slug($this->nama_metode);

        // Ensure unique
        $exists = PaymentMethod::where('kode_metode', $kode)->exists();
        if ($exists) {
            $this->addError('kode_metode', 'Kode metode pembayaran sudah digunakan.');
            return;
        }

        PaymentMethod::create([
            'nama_metode' => $this->nama_metode,
            'kode_metode' => $kode,
            'is_active' => $this->is_active,
        ]);

        $this->dispatch('showToast', message: 'Metode Pembayaran Berhasil Ditambahkan', type: 'success', title: 'Success');
        $this->closeModal();
    }

    public function edit($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $this->payment_method_id = $method->id;
        $this->nama_metode = $method->nama_metode;
        $this->kode_metode = $method->kode_metode;
        $this->is_active = $method->is_active;

        $this->isEdit = true;
        $this->dispatch('open-modal', name: 'modal-payment-method');
    }

    public function update()
    {
        $this->validate();

        $kode = $this->kode_metode ?: Str::slug($this->nama_metode);

        // Ensure unique excluding current
        $exists = PaymentMethod::where('kode_metode', $kode)->where('id', '!=', $this->payment_method_id)->exists();
        if ($exists) {
            $this->addError('kode_metode', 'Kode metode pembayaran sudah digunakan.');
            return;
        }

        $method = PaymentMethod::findOrFail($this->payment_method_id);
        $method->update([
            'nama_metode' => $this->nama_metode,
            'kode_metode' => $kode,
            'is_active' => $this->is_active,
        ]);

        $this->dispatch('showToast', message: 'Metode Pembayaran Berhasil Diperbarui', type: 'success', title: 'Success');
        $this->closeModal();
    }

    public function toggleStatus($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->is_active = !$method->is_active;
        $method->save();
        $this->dispatch('showToast', message: 'Status Metode Pembayaran Berhasil Diubah', type: 'success', title: 'Success');
    }

    public function closeModal()
    {
        $this->dispatch('close-modal', name: 'modal-payment-method');
        $this->resetFields();
    }

    public function resetFields()
    {
        $this->nama_metode = '';
        $this->kode_metode = '';
        $this->is_active = true;
        $this->payment_method_id = null;
        $this->resetErrorBag();
    }
}
