<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CreateUser extends Component
{
    // Properti Form
    public $name, $email, $phone, $password, $role_selected;

    // Properti Logic Edit
    public $userId = null;
    public $isEdit = false;

    // Lifecycle Mount (Dijalankan saat halaman dimuat)
    public function mount($userId = null)
    {
        // dd($userId);
        // Jika ada ID di URL, berarti Mode Edit
        if ($userId) {
            try {
                $decodedId = base64_decode($userId);
                $user = User::findOrFail($decodedId);

                $this->userId = $user->id;
                $this->isEdit = true;

                // Isi Form dengan data user
                $this->name = $user->name;
                $this->email = $user->email;
                $this->phone = $user->phone;
                // Password tidak diisi karena terenkripsi

                // Ambil role pertama (karena form kita single select)
                $this->role_selected = $user->roles->first()?->name;
            } catch (\Exception $e) {
                // Jika ID base64 error/tidak ditemukan, redirect ke index
                return redirect()->route('users.index');
            }
        }
    }

    public function save()
    {
        // dd($this->phone);
        // 1. Validasi Dinamis
        $this->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|numeric',
            'role_selected' => 'required|exists:roles,name',

            // Email: Unique, tapi abaikan user ini jika sedang edit
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($this->userId)
            ],

            // Password: Wajib saat Create, Nullable (boleh kosong) saat Edit
            'password' => $this->isEdit ? 'nullable|min:6' : 'required|min:6',
        ]);

        if ($this->isEdit) {
            // === LOGIC UPDATE ===
            $user = User::find($this->userId);

            $dataToUpdate = [
                'name'  => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
            ];

            // Hanya update password jika input tidak kosong
            if (!empty($this->password)) {
                $dataToUpdate['password'] = Hash::make($this->password);
            }

            $user->update($dataToUpdate);

            // Sync Role (Ganti role lama dengan yang baru)
            $user->syncRoles($this->role_selected);

            $message = 'Data user berhasil diperbarui.';
        } else {
            // === LOGIC CREATE ===
            $user = User::create([
                'name'     => $this->name,
                'email'    => $this->email,
                'phone'    => $this->phone,
                'password' => Hash::make($this->password),
            ]);

            $user->assignRole($this->role_selected);
            $this->reset(['name', 'email', 'phone', 'password', 'role_selected']);

            $message = 'User berhasil ditambahkan.';
        }

        // Kirim Notifikasi
        $this->dispatch('showToast', type: 'success', message: $message);
    }
    public function render()
    {
        return view('livewire.user.create-user', [
            'roles' => Role::all()
        ]);
    }
}
