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
    // Properti Form Dasar
    public $name, $email, $phone, $password, $role_selected, $branch_id;

    // Properti Form Gaji & Cuti (Payroll)
    public $gaji_pokok = 0;
    public $tunjangan_harian = 0;
    public $potongan_terlambat = 0;
    public $potongan_alpha = 0;
    public $hak_cuti = 12;

    // Properti Logic Edit
    public $userId = null;
    public $isEdit = false;
    public $title = 'Tambah Pengguna Baru';
    public $backUrl = '/users';

    // Lifecycle Mount (Dijalankan saat halaman dimuat)
    public function mount($userId = null)
    {
        // Set default branch_id untuk manager
        if (!auth()->user()->hasRole('superadmin')) {
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
                if (!auth()->user()->hasRole('superadmin') && $user->branch_id !== auth()->user()->branch_id) {
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

                // Isi Form Payroll
                $this->gaji_pokok = $user->gaji_pokok ?? 0;
                $this->tunjangan_harian = $user->tunjangan_harian ?? 0;
                $this->potongan_terlambat = $user->potongan_terlambat ?? 0;
                $this->potongan_alpha = $user->potongan_alpha ?? 0;
                $this->hak_cuti = $user->hak_cuti ?? 12;

                // Ambil role pertama
                $this->role_selected = $user->roles->first()?->name;
            } catch (\Exception $e) {
                return redirect()->route('users.index');
            }
        }
    }

    public function save()
    {
        // 1. Validasi Dinamis
        $this->validate([
            'name'          => 'required|string|max:255',
            'phone'         => 'nullable|numeric',
            'role_selected' => 'required|exists:roles,name',

            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($this->userId)
            ],

            'branch_id' => [
                Rule::requiredIf(function () {
                    return $this->role_selected !== 'superadmin';
                }),
                'nullable',
                'exists:branches,id'
            ],

            'password' => $this->isEdit ? 'nullable|min:6' : 'required|min:6',

            // Validasi Komponen Gaji
            'gaji_pokok'         => 'nullable|numeric|min:0',
            'tunjangan_harian'   => 'nullable|numeric|min:0',
            'potongan_terlambat' => 'nullable|numeric|min:0',
            'potongan_alpha'     => 'nullable|numeric|min:0',
            'hak_cuti'           => 'nullable|integer|min:0',
        ]);

        $payrollData = [
            'gaji_pokok'         => empty($this->gaji_pokok) ? 0 : $this->gaji_pokok,
            'tunjangan_harian'   => empty($this->tunjangan_harian) ? 0 : $this->tunjangan_harian,
            'potongan_terlambat' => empty($this->potongan_terlambat) ? 0 : $this->potongan_terlambat,
            'potongan_alpha'     => empty($this->potongan_alpha) ? 0 : $this->potongan_alpha,
            'hak_cuti'           => empty($this->hak_cuti) ? 12 : $this->hak_cuti,
        ];

        if ($this->isEdit) {
            // === LOGIC UPDATE ===
            $user = User::find($this->userId);

            $dataToUpdate = array_merge([
                'name'  => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'branch_id' => $this->branch_id,
            ], $payrollData);

            if (!empty($this->password)) {
                $dataToUpdate['password'] = Hash::make($this->password);
            }

            $user->update($dataToUpdate);
            $user->syncRoles($this->role_selected);

            $message = 'Data user & parameter kompensasi gaji berhasil diperbarui.';
        } else {
            // === LOGIC CREATE ===
            $dataToCreate = array_merge([
                'name'     => $this->name,
                'email'    => $this->email,
                'phone'    => $this->phone,
                'password' => Hash::make($this->password),
                'branch_id' => auth()->user()->hasRole('superadmin') ? $this->branch_id : auth()->user()->branch_id,
            ], $payrollData);

            $user = User::create($dataToCreate);
            $user->assignRole($this->role_selected);

            $this->reset(['name', 'email', 'phone', 'password', 'role_selected', 'gaji_pokok', 'tunjangan_harian', 'potongan_terlambat', 'potongan_alpha']);
            $this->hak_cuti = 12;
            
            if (!auth()->user()->hasRole('superadmin')) {
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
        if (!$currentUser->hasRole('superadmin')) {
            $roleQuery->whereIn('name', ['kasir', 'karyawan']);
        }

        return view('livewire.user.create-user', [
            'roles' => $roleQuery->latest()->get(),
            'branches' => $currentUser->hasRole('superadmin') ? \App\Models\Branch::all() : [],
            'title' => $this->title,
            'backUrl' => $this->backUrl
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
