<?php

namespace App\Livewire\User;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class CreateUser extends Component
{
    // Properti Form Dasar
    public $name;

    public $email;

    public $phone;

    public $password;

    public $branch_id;

    public $jabatan_id;

    public $role_selected = []; // Berubah jadi array untuk multiple roles

    // Properti Form Gaji & Cuti (Payroll)
    public $gaji_pokok = 0;

    public $tunjangan_harian = 0;

    public $hak_cuti = 12;

    // Properti Logic Edit
    public $userId = null;

    public $isEdit = false;

    public $title = 'Tambah Pengguna Baru';

    public $backUrl = '/user';

    // Lifecycle Mount (Dijalankan saat halaman dimuat)
    public function mount($userId = null)
    {
        // Set default branch_id untuk manager
        if (! auth()->user()->hasRole('superadmin')) {
            $this->branch_id = auth()->user()->branch_id;
        }

        // Pastikan role karyawan dan manager terdaftar di sistem secara dinamis
        Role::firstOrCreate(['name' => 'karyawan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        // Jika ada ID di URL, berarti Mode Edit
        if ($userId) {
            try {
                $decodedId = base64_decode($userId);
                $user = User::findOrFail($decodedId);

                // Audit Role Check: Manager hanya bisa mengedit user di cabangnya sendiri
                if (! auth()->user()->hasRole('superadmin') && $user->branch_id !== auth()->user()->branch_id) {
                    abort(403, 'Aksi ditolak. Anda hanya bisa mengelola user di cabang Anda sendiri.');
                }

                $this->userId = $user->id;
                $this->isEdit = true;
                $this->title = 'Edit Pengguna';

                // Isi Form dengan data user
                $this->name = $user->name;
                $this->email = $user->email;
                $this->phone = $user->phone;
                $this->branch_id = $user->branch_id;
                $this->jabatan_id = $user->jabatan_id;

                // Isi Form Payroll
                $this->gaji_pokok = $user->gaji_pokok ?? 0;
                $this->tunjangan_harian = $user->tunjangan_harian ?? 0;
                $this->hak_cuti = $user->hak_cuti ?? 12;

                // Ambil semua role menjadi array
                $this->role_selected = $user->roles->pluck('name')->toArray();
            } catch (\Exception $e) {
                return redirect()->route('user.index');
            }
        }
    }

    public function save()
    {
        // 1. Validasi Dinamis
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|numeric',
            'jabatan_id' => 'required|exists:jabatans,id',
            'role_selected' => 'required|array|min:1',
            'role_selected.*' => 'exists:roles,name',

            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($this->userId),
            ],

            'branch_id' => [
                Rule::requiredIf(function () {
                    return ! in_array('superadmin', $this->role_selected);
                }),
                'nullable',
                'exists:branches,id',
            ],

            'password' => $this->isEdit ? 'nullable|min:6' : 'required|min:6',

            // Validasi Komponen Gaji
            'gaji_pokok' => 'nullable|numeric|min:0',
            'tunjangan_harian' => 'nullable|numeric|min:0',
            'hak_cuti' => 'nullable|integer|min:0',
        ]);

        $payrollData = [
            'gaji_pokok' => empty($this->gaji_pokok) ? 0 : $this->gaji_pokok,
            'tunjangan_harian' => empty($this->tunjangan_harian) ? 0 : $this->tunjangan_harian,
            'hak_cuti' => empty($this->hak_cuti) ? 12 : $this->hak_cuti,
        ];

        if ($this->isEdit) {
            // === LOGIC UPDATE ===
            $user = User::find($this->userId);

            $dataToUpdate = array_merge([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'branch_id' => $this->branch_id,
                'jabatan_id' => $this->jabatan_id,
            ], $payrollData);

            if (! empty($this->password)) {
                $dataToUpdate['password'] = Hash::make($this->password);
            }

            $user->update($dataToUpdate);
            $user->syncRoles($this->role_selected);

            $message = 'Data user & parameter kompensasi gaji berhasil diperbarui.';
        } else {
            // === LOGIC CREATE ===
            $dataToCreate = array_merge([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'jabatan_id' => $this->jabatan_id,
                'password' => Hash::make($this->password),
                'branch_id' => auth()->user()->hasRole('superadmin') ? $this->branch_id : auth()->user()->branch_id,
            ], $payrollData);

            $user = User::create($dataToCreate);
            $user->assignRole($this->role_selected);

            $this->reset(['name', 'email', 'phone', 'jabatan_id', 'password', 'role_selected', 'gaji_pokok', 'tunjangan_harian']);
            $this->hak_cuti = 12;

            if (! auth()->user()->hasRole('superadmin')) {
                $this->branch_id = auth()->user()->branch_id;
            }

            $message = 'User baru beserta struktur gaji bulanan berhasil ditambahkan.';
        }

        $this->dispatch('showToast', type: 'success', message: $message);
    }

    public function render()
    {
        $currentUser = auth()->user();
        $roleQuery = Role::query();

        // Audit Role: Manager hanya boleh mengelola role 'kasir' dan 'karyawan'
        if (! $currentUser->hasRole('superadmin')) {
            $roleQuery->whereIn('name', ['kasir', 'karyawan']);
        }

        return view('livewire.user.create-user', [
            'roles' => $roleQuery->latest()->get(),
            'branches' => $currentUser->hasRole('superadmin') ? \App\Models\Branch::all() : [],
            'jabatans' => \App\Models\Jabatan::all(),
            'title' => $this->title,
            'backUrl' => $this->backUrl,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
