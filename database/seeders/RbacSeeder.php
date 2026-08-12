<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'operate pos',
            'approve general discount',
            'approve all discount',
            'manage discount',
            'manage menu',
            'manage pengeluaran',
            'edit finished transaction',
            'view sensitive reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $kasir = Role::firstOrCreate(['name' => 'kasir']);

        // Give all permissions to superadmin
        $superadmin->givePermissionTo(Permission::all());
        
        $manager->givePermissionTo([
            'operate pos',
            'approve general discount',
            'approve all discount',
            'manage discount',
            'manage menu',
            'manage pengeluaran',
            'edit finished transaction',
            'view sensitive reports',
        ]);

        $kasir->givePermissionTo([
            'operate pos',
            'approve general discount',
        ]);
    }
}
