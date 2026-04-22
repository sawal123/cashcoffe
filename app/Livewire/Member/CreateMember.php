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
        if ($this->memberId) {
            $member = Member::with('user')->find(\base64_decode($this->memberId));
            if ($member) {
                $this->name    = $member->user->name ?? '';
                $this->email   = $member->user->email ?? '';
                $this->phone   = $member->phone;
                $this->address = $member->address;
            }
        }
    }
    public function update($id)
    {
        $validated = $this->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'required|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        $member = Member::with('user')->find(\base64_decode($id));
        // update user
        if ($member->user) {
            $userUpdate = ['name' => $this->name];
            if ($this->email) {
                $userUpdate['email'] = $this->email;
            }
            $member->user->update($userUpdate);
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
            'email'    => 'nullable|email|max:255',
            'phone'    => 'required|string|max:20',
            'address'  => 'nullable|string|max:255',
        ]);
        $email = $this->email ?: 'member_' . time() . '_' . uniqid() . '@member.com';

        $user = User::create([
            'name'     => $this->name,
            'email'    => $email,
            'password' => Hash::make('member123'),
        ]);
        Member::create([
            'user_id' => $user->id,
            'phone'   => $this->phone,
            'address' => $this->address,
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
