<?php

namespace App\Livewire\SalesChannel;

use App\Models\SalesChannel;
use Livewire\Component;

class Index extends Component
{
    public $nama_channel, $is_active = true, $channel_id;
    public $isEdit = false;

    protected $rules = [
        'nama_channel' => 'required|string|max:255',
        'is_active' => 'boolean',
    ];

    public function render()
    {
        $channels = SalesChannel::all();
        return view('livewire.sales-channel.index', [
            'channels' => $channels,
            'title' => 'Daftar Sales Channel'
        ])->layout('layouts.app', ['title' => 'Sales Channel']);
    }

    public function create()
    {
        $this->resetFields();
        $this->isEdit = false;
        $this->dispatch('open-modal', name: 'modal-channel');
    }

    public function store()
    {
        $this->validate();

        SalesChannel::create([
            'nama_channel' => $this->nama_channel,
            'is_active' => $this->is_active,
        ]);

        $this->dispatch('showToast', message: 'Sales Channel Berhasil Ditambahkan', type: 'success', title: 'Success');
        $this->closeModal();
    }

    public function edit($id)
    {
        $channel = SalesChannel::findOrFail($id);
        $this->channel_id = $channel->id;
        $this->nama_channel = $channel->nama_channel;
        $this->is_active = $channel->is_active;

        $this->isEdit = true;
        $this->dispatch('open-modal', name: 'modal-channel');
    }

    public function update()
    {
        $this->validate();

        $channel = SalesChannel::findOrFail($this->channel_id);
        $channel->update([
            'nama_channel' => $this->nama_channel,
            'is_active' => $this->is_active,
        ]);

        $this->dispatch('showToast', message: 'Sales Channel Berhasil Diperbarui', type: 'success', title: 'Success');
        $this->closeModal();
    }

    public function toggleStatus($id)
    {
        $channel = SalesChannel::findOrFail($id);
        $channel->is_active = !$channel->is_active;
        $channel->save();
        $this->dispatch('showToast', message: 'Status Channel Berhasil Diubah', type: 'success', title: 'Success');
    }

    public function closeModal()
    {
        $this->dispatch('close-modal', name: 'modal-channel');
        $this->resetFields();
    }

    public function resetFields()
    {
        $this->nama_channel = '';
        $this->is_active = true;
        $this->channel_id = null;
    }
}
