<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use App\Models\User;

echo "ROLES IN DB:\n";
foreach (Role::all() as $role) {
    echo "- " . $role->name . "\n";
}

echo "\nUSERS AND ROLES:\n";
foreach (User::with('roles')->get() as $user) {
    echo "- " . $user->email . " : " . $user->roles->pluck('name')->implode(', ') . "\n";
}
