<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;

$attempts = [
    ['username' => 'admin', 'password' => '12345678'],
    ['username' => 'admin@mutu-rsys.id', 'password' => '12345678'],
];

foreach ($attempts as $cred) {
    $field = filter_var($cred['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    $ok = Auth::attempt([$field => $cred['username'], 'password' => $cred['password']]);
    echo "Login dengan " . $field . " '" . $cred['username'] . "' dan password '12345678': " . ($ok ? "BERHASIL (OK)" : "GAGAL") . "\n";
}
