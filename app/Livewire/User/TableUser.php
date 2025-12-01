<?php

namespace App\Livewire\User;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class TableUser extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $newRoleName = '';
    // Reset pagination saat melakukan pencarian
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function createRole()
    {
        // 1. Validasi
        $this->validate([
            'newRoleName' => 'required|unique:roles,name|min:3',
        ], [
            'newRoleName.required' => 'Nama role tidak boleh kosong.',
            'newRoleName.unique' => 'Nama role sudah ada.',
            'newRoleName.min' => 'Nama role minimal 3 karakter.',
        ]);

        // 2. Simpan ke Database (Spatie)
        // Guard name biasanya 'web', sesuaikan jika Anda pakai guard lain
        Role::create(['name' => strtolower($this->newRoleName), 'guard_name' => 'web']);

        // 3. Reset Input
        $this->reset('newRoleName');

        // 4. Tutup Modal & Kirim Notifikasi
        $this->dispatch('close-modal', name: 'add-role'); // Pastikan x-mdl support event ini atau gunakan x-on:close-modal

        // Dispatch event untuk Toast Notification (jika ada komponen toast)
        $this->dispatch('showToast', type: 'success', message: 'Role baru berhasil ditambahkan!');
    }
    public function deleteRole($encodedId)
    {
        // 1. Decode ID (karena dikirim via base64 dari frontend)
        $id = base64_decode($encodedId);

        // 2. Cari Role
        $role = Role::find($id);

        if ($role) {
            // Validasi: Cek apakah role masih dipakai user
            if ($role->users()->count() > 0) {
                $this->dispatch('showToast', type: 'error', message: 'Gagal! Role ini masih digunakan oleh user.');
                return;
            }

            // 3. Hapus
            $role->delete();
            $this->dispatch('showToast', type: 'success', message: 'Role berhasil dihapus.');
        } else {
            $this->dispatch('showToast', type: 'error', message: 'Data role tidak ditemukan.');
        }
    }

    // ...

    public function deleteUser($encodedId)
    {
        // 1. Decode ID
        $id = base64_decode($encodedId);
        // dd(auth()->id());
        // 2. Cari User di Database
        $user = User::find($id);

        // 3. Validasi Jika User Tidak Ditemukan
        if (!$user) {
            $this->dispatch('showToast', type: 'error', message: 'Data user tidak ditemukan.');
            return;
        }

        // 4. SECURITY: Cegah Hapus Diri Sendiri
        if ($user->id === auth()->id()) {
            $this->dispatch('close-modal', name: 'confirm-delete');
            $this->dispatch('showToast', type: 'error', message: 'Anda tidak bisa menghapus akun sendiri yang sedang login!');
            return;
        }

        // 5. SECURITY: Cegah Hapus Super Admin (Opsional, sesuaikan nama role)
        if ($user->hasRole('super-admin')) {
            $this->dispatch('close-modal', name: 'confirm-delete');
            $this->dispatch('showToast', type: 'error', message: 'Akun Super Admin dilindungi dan tidak bisa dihapus.');
            return;
        }

        // 6. Hapus File Avatar (Jika ada)
        // Pastikan Anda mengimport Storage: use Illuminate\Support\Facades\Storage;
        if ($user->avatar && \Illuminate\Support\Facades\Storage::exists('public/' . $user->avatar)) {
            \Illuminate\Support\Facades\Storage::delete('public/' . $user->avatar);
        }

        // 7. Hapus User
        $user->delete();

        // 8. Tutup Modal & Notifikasi
        $this->dispatch('close-modal', name: 'confirm-delete');
        $this->dispatch('showToast', type: 'success', message: 'User berhasil dihapus selamanya.');
    }

    public function render()
    {
        $users = User::query()
            ->with('roles') // <--- Tambahkan ini untuk performa (Eager Loading)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate($this->perPage);
        $all_roles = Role::latest()->get();
        return view('livewire.user.table-user', [
            'users' => $users,
            'all_roles' => $all_roles // Kirim ke view
        ]);
    }
}
