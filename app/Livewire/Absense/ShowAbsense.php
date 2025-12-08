<?php

namespace App\Livewire\Absense;

use App\Models\User;
use App\Models\Absensi;
use Livewire\Component;
use Livewire\WithPagination;

class ShowAbsense extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $userId;
    public $month;
    public $year;

    public function mount($userId)
    {
        $this->userId = $userId;
        $this->month = now()->month;
        $this->year = now()->year;
    }
    public function render()
    {
        $user = User::findOrFail($this->userId);

        $absensis = Absensi::where('user_id', $this->userId)
            ->whereMonth('tanggal', $this->month)
            ->whereYear('tanggal', $this->year)
            ->orderBy('tanggal', 'asc')
            ->paginate(10);
        return view('livewire.absense.show-absense', compact('user', 'absensis'));
    }
}
