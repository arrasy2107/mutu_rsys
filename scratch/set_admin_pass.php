<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$user = User::where('email', 'admin@mutu-rsys.id')->first();
if ($user) {
    $user->update([
        'must_change_password' => false,
        'password' => bcrypt('password'),
    ]);
    echo "Admin password set to 'password' and must_change_password=false\n";
}
