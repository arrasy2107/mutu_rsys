<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$admin = User::find(1);
if ($admin) {
    $admin->update([
        'username' => 'admin',
        'email' => 'admin@mutu-rsys.id',
        'password' => Hash::make('12345678'),
        'must_change_password' => false,
        'aktif' => true,
    ]);
    echo "SUCCESS: Admin account updated.\n";
    echo "Username: admin\n";
    echo "Email: admin@mutu-rsys.id\n";
    echo "Password: Reset to default '12345678'\n";
    echo "Active: true\n";
    echo "Must Change Password: false\n";
}
