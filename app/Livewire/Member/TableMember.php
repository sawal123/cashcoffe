<?php

namespace App\Livewire\Member;

use App\Models\Member;
use Livewire\Component;
use Livewire\WithPagination;

class TableMember extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage  = 10;
    public $memberId;

    // agar pagination tidak reset ketika mengetik
    protected $updatesQueryString = ['search'];



    // proses hapus
    public function deleteMember($id)
    {
        // dd($id);
        $member = Member::find(base64_decode($id));

        if (!$member) {
            $this->dispatch('showToast', message: 'Member tidak ditemukan', type: 'error', title: 'Error');
            return;
        }

        // hapus user terkait (optional: cek dulu)
        if ($member->user) {
            $member->user->delete();
        }

        // hapus data member
        $member->delete();

        $this->dispatch('close-delete-modal');

        $this->dispatch('showToast', message: 'Member berhasil dihapus', type: 'error', title: 'Error');
    }
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $members = Member::with('user')
            ->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orWhere(function ($q) {
                $q->where('phone', 'like', '%' . $this->search . '%')
                    ->orWhere('address', 'like', '%' . $this->search . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.member.table-member', [
            'members' => $members
        ]);
    }
}
