<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('role', 'superadmin')->orWhere('role', 'komite_mutu')->first();
if (!$user) {
    echo "User not found\n";
    exit(1);
}

// Act as user
Illuminate\Support\Facades\Auth::login($user);

$routes = [
    '/dashboard',
    '/indicator-selections',
    '/indicator-selections?tipe=rs',
    '/indicator-selections/create?tipe=rs',
    '/indicators',
    '/indicators/create?prefill_jenis=IMP-RS',
    '/hospital/aggregation',
    '/hospital/analysis',
    '/hospital/pdsa',
];

echo "Testing Authenticated Routes for Komite Mutu (" . $user->email . "):\n";
echo str_repeat("=", 70) . "\n";

foreach ($routes as $uri) {
    $request = Illuminate\Http\Request::create($uri, 'GET');
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    echo sprintf("[%d] %s\n", $status, $uri);
    if ($status >= 400) {
        echo "Error Content:\n" . substr($response->getContent(), 0, 500) . "\n";
    }
}

echo str_repeat("=", 70) . "\n";
echo "All route tests completed.\n";
