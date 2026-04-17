<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename 'admin' to 'manager' (Jika ada)
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->update(['name' => 'manager']);
        } else {
            // Jika belum ada, buat baru
            Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        }

        // 2. Buat 'kasir' jika belum ada
        Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);

        // 3. Pastikan 'superadmin' ada
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kebalikan dari up (opsional, karena ini restrukturisasi permanen)
    }
};
