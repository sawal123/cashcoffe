<?php

namespace App\Livewire\Absensi;

use Livewire\Component;

class Login extends Component
{
    public function render()
    {
        return view('livewire.absensi.login')->layout('layouts.absensi');
    }
}
