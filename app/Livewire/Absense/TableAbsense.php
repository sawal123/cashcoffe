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

        $usersQuery = User::whereHas('roles', fn($q) => $q->where('name', 'karyawan'));
        
        $totalKaryawan = (clone $usersQuery)->count();
        $hadirCount = \App\Models\Absensi::where('tanggal', $today)->count();
        $terlambatCount = \App\Models\Absensi::where('tanggal', $today)->where('status', 'terlambat')->count();
        $belumAbsenCount = $totalKaryawan - $hadirCount;

        $users = $usersQuery
            ->where('name', 'like', '%' . $this->search . '%')
            ->with(['absensis' => function ($q) use ($today) {
                $q->where('tanggal', $today);
            }, 'roles'])
            ->paginate($this->perPage);

        return view('livewire.absense.table-absense', [
            'users' => $users,
            'stats' => [
                'total' => $totalKaryawan,
                'hadir' => $hadirCount,
                'terlambat' => $terlambatCount,
                'belum' => $belumAbsenCount,
            ],
            'title' => 'Monitoring Absensi Karyawan'
        ])->layout('layouts.app', ['title' => 'Absensi']);
    }
}
