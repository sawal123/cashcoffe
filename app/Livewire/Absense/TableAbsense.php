<?php

namespace App\Livewire\Absense;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class TableAbsense extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $search = '';
    public $perPage = 10;

    protected $queryString = ['search', 'page'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $today = now()->toDateString();

        $users = User::whereHas('roles', fn($q) => $q->where('name', 'karyawan'))
            ->where('name', 'like', '%' . $this->search . '%')
            ->with(['absensis' => function ($q) use ($today) {
                $q->where('tanggal', $today);
            }])
            ->paginate($this->perPage);

        return view('livewire.absense.table-absense', compact('users'));
    }
}
