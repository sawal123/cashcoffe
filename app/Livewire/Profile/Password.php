<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Password extends Component
{
    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    protected $rules = [
        'current_password' => 'required',
        'new_password' => 'required|min:8|confirmed',
    ];

    public function update()
    {
        $this->validate();

        $user = Auth::user();

        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Password saat ini tidak sesuai.');
            return;
        }

        $user->update([
            'password' => Hash::make($this->new_password)
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->dispatch('showToast', type: 'success', message: 'Password berhasil diperbarui.');
    }

    public function render()
    {
        return view('livewire.profile.password')->layout('layouts.absensi', ['title' => 'Ubah Password']);
    }
}
