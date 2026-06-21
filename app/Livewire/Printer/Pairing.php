<?php

namespace App\Livewire\Printer;

use Livewire\Component;

class Pairing extends Component
{
    public string $title = 'Pairing Printer';

    public function render()
    {
        return view('livewire.printer.pairing', [
            'title' => $this->title,
        ])->layout('layouts.app', [
            'title' => $this->title,
        ]);
    }
}
