<?php

namespace App\Livewire\Member;

use App\Models\User;
use App\Models\Member;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;

class CreateMember extends Component
{
    public $name, $email, $password, $phone, $address;
    public $memberId;

    public function mount()
    {
        // dd(\base64_decode($this->memberId));
        $member = Member::with('user')->find(\base64_decode($this->memberId));
        // dd($member);
        $this->name    = $member->user->name ?? '';
        $this->email   = $member->user->email ?? '';
        $this->phone   = $member->phone;
        $this->address = $member->address;
    }
    public function update($id)
    {
        $validated = $this->validate([
            'name'    => 'required',
            'email'   => 'nullable|email',
            'phone'   => 'nullable',
            'address' => 'nullable',
        ]);

        $member = Member::with('user')->find(\base64_decode($id));
        // dd($id);
        // update user
        if ($member->user) {
            $member->user->update([
                'name'  => $this->name,
                'email' => $this->email,
            ]);
        }

        // update member
        $member->update([
            'phone'   => $this->phone,
            'address' => $this->address,
        ]);


        $this->dispatch('showToast', message: 'Member Berhasil Diupdate', type: 'success', title: 'Success');
    }

    public function simpan()
    {
        // Validasi
        $validated = $this->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'nullable|string|max:20',
            'address'  => 'nullable|string|max:255',
        ]);
        $user = User::create([
            'name'     => $this->name,
            'email'    => $this->email ?? null,
            'password' => Hash::make('member123'),
        ]);
        Member::create([
            'user_id' => $user->id ?? null,
            'phone'   => $this->phone,
            'address' => $this->address ?? null,
        ]);

        $this->reset(['name', 'email', 'password', 'phone', 'address']);

        // Optional: Kirim event ke browser untuk menutup modal atau menampilkan alert
        $this->dispatch('member-created');

        $this->dispatch('showToast', message: 'Member Berhasil Ditambah', type: 'success', title: 'Success');
    }
    public function render()
    {
        return view('livewire.member.create-member');
    }
}
