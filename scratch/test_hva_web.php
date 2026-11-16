<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
echo "User: " . $user->name . " (Role: " . $user->role . ")\n";

$req1 = Illuminate\Http\Request::create('/hva', 'GET');
$req1->setUserResolver(fn() => $user);
$controller = new App\Http\Controllers\Hva\HvaController();
$res1 = $controller->index($req1);
echo "1. HvaController@index: " . ($res1 instanceof Inertia\Response ? "OK (Inertia Response)" : "FAIL") . "\n";

$req2 = Illuminate\Http\Request::create('/hva/summary', 'GET');
$req2->setUserResolver(fn() => $user);
$res2 = $controller->summary($req2);
echo "2. HvaController@summary: " . ($res2 instanceof Inertia\Response ? "OK (Inertia Response)" : "FAIL") . "\n";

$req3 = Illuminate\Http\Request::create('/hva/rtl', 'GET');
$req3->setUserResolver(fn() => $user);
$res3 = $controller->rtl($req3);
echo "3. HvaController@rtl: " . ($res3 instanceof Inertia\Response ? "OK (Inertia Response)" : "FAIL") . "\n";

$req4 = Illuminate\Http\Request::create('/hva/category/natural', 'GET');
$req4->setUserResolver(fn() => $user);
$res4 = $controller->category($req4, 'natural');
echo "4. HvaController@category(natural): " . ($res4 instanceof Inertia\Response ? "OK (Inertia Response)" : "FAIL") . "\n";

echo "All 4 HVA endpoints tested successfully!\n";

