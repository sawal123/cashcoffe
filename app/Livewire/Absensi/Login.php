<?php

namespace App\Livewire\Absensi;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Login extends Component
{
    public $identifier = '';
    public $password = '';
    public $remember = false;

    public function authenticate()
    {
        $this->validate([
            'identifier' => 'required',
            'password'   => 'required',
        ], [
            'identifier.required' => 'ID Pegawai / Email wajib diisi.',
            'password.required'   => 'Kata sandi wajib diisi.',
        ]);

        $user = User::where('email', $this->identifier)
            ->orWhere('phone', $this->identifier)
            ->orWhere('id', $this->identifier)
            ->first();

        if ($user && Hash::check($this->password, $user->password)) {
            if (!$user->hasRole('karyawan')) {
                session()->flash('error', 'Akses ditolak. Akun ini bukan terdaftar sebagai karyawan.');
                return;
            }

            Auth::login($user, $this->remember);
            return redirect()->to('/absen');
        }

        session()->flash('error', 'ID/Email atau kata sandi tidak valid.');
    }

    public function render()
    {
        return view('livewire.absensi.login')->layout('layouts.absensi');
    }
}
