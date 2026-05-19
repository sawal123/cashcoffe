<?php

namespace App\Livewire\Admin\Shift;

use App\Models\User;
use App\Models\Shift;
use App\Models\UserShift;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class ShiftSchedule extends Component
{
    use WithPagination;

    // Filter & Search
    public $searchUser = '';
    public $selectedUserId;
    
    // Calendar State
    public $currentMonth;
    public $currentYear;
    
    // Bulk Action State
    public $selectedDates = [];
    public $targetShiftId;

    public function mount()
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
    }

    public function selectUser($id)
    {
        $this->selectedUserId = $id;
        $this->selectedDates = [];
    }

    public function prevMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function toggleDate($date)
    {
        if (in_array($date, $this->selectedDates)) {
            $this->selectedDates = array_diff($this->selectedDates, [$date]);
        } else {
            $this->selectedDates[] = $date;
        }
    }

    public function saveBulkSchedule()
    {
        $this->validate([
            'selectedUserId' => 'required|exists:users,id',
            'targetShiftId' => 'required|exists:shifts,id',
            'selectedDates' => 'required|array|min:1',
        ]);

        $addedCount = 0;
        $skippedCount = 0;

        foreach ($this->selectedDates as $date) {
            // Check if user already has ANY shift on this date
            $existing = UserShift::where('user_id', $this->selectedUserId)
                ->where('tanggal', $date)
                ->exists();

            if (!$existing) {
                UserShift::create([
                    'user_id' => $this->selectedUserId,
                    'shift_id' => $this->targetShiftId,
                    'tanggal' => $date,
                    'is_double_shift' => ($this->targetShiftId == 3), // Auto flag if ID 3 is selected
                ]);
                $addedCount++;
            } else {
                $skippedCount++;
            }
        }

        $msg = $addedCount . ' jadwal berhasil ditambahkan.';
        if ($skippedCount > 0) {
            $msg .= ' (' . $skippedCount . ' tanggal dilewati karena sudah ada jadwal).';
        }

        session()->flash('success', $msg);
        $this->selectedDates = [];
        $this->targetShiftId = null;
    }

    public function deleteSchedule($id)
    {
        UserShift::find($id)->delete();
        session()->flash('success', 'Jadwal berhasil dihapus.');
    }

    public function render()
    {
        $users = User::role('karyawan')
            ->where('name', 'like', '%' . $this->searchUser . '%')
            ->paginate(10);
            
        $shifts = Shift::all();
        
        $calendar = $this->generateCalendar();
        
        $userSchedules = [];
        $userLeaves = [];

        if ($this->selectedUserId) {
            $userSchedules = UserShift::with('shift')
                ->where('user_id', $this->selectedUserId)
                ->whereMonth('tanggal', $this->currentMonth)
                ->whereYear('tanggal', $this->currentYear)
                ->get()
                ->groupBy(fn($item) => $item->tanggal->format('Y-m-d'));

            // Ambil data cuti/izin yang sudah disetujui (Approved)
            $leaves = \App\Models\IzinAbsensi::where('user_id', $this->selectedUserId)
                ->where('status', 'approved')
                ->where(function($q) {
                    $q->whereMonth('tanggal_mulai', $this->currentMonth)
                      ->whereYear('tanggal_mulai', $this->currentYear)
                      ->orWhereMonth('tanggal_selesai', $this->currentMonth)
                      ->orWhereYear('tanggal_selesai', $this->currentYear);
                })
                ->get();

            foreach ($leaves as $leave) {
                $start = Carbon::parse($leave->tanggal_mulai);
                $end = Carbon::parse($leave->tanggal_selesai);
                
                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    if ($date->month == $this->currentMonth && $date->year == $this->currentYear) {
                        $userLeaves[$date->format('Y-m-d')] = $leave;
                    }
                }
            }
        }

        return view('livewire.admin.shift.shift-schedule', compact('users', 'shifts', 'calendar', 'userSchedules', 'userLeaves'))->layout('layouts.app');
    }

    private function generateCalendar()
    {
        $startOfMonth = Carbon::create($this->currentYear, $this->currentMonth, 1);
        $daysInMonth = $startOfMonth->daysInMonth;
        $dayOfWeek = $startOfMonth->dayOfWeek; // 0 (Sun) to 6 (Sat)
        
        $calendar = [];
        
        // Padding for previous month
        for ($i = 0; $i < $dayOfWeek; $i++) {
            $calendar[] = null;
        }
        
        // Current month days
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $calendar[] = Carbon::create($this->currentYear, $this->currentMonth, $day)->toDateString();
        }
        
        return $calendar;
    }
}
