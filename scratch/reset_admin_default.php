<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$admin = User::where('username', 'admin')->orWhere('email', 'admin@mutu-rsys.id')->first();

if ($admin) {
    $admin->update([
        'password' => Hash::make('12345678'),
        'must_change_password' => false,
    ]);
    echo "BERHASIL:\n";
    echo "ID: " . $admin->id . "\n";
    echo "Name: " . $admin->name . "\n";
    echo "Username: " . $admin->username . "\n";
    echo "Email: " . $admin->email . "\n";
    echo "Password: Reset to default '12345678'\n";
    echo "Must Change Password: false (langsung masuk ke dashboard)\n";
} else {
    echo "Admin user not found!\n";
}
