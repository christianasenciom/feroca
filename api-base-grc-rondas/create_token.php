<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\Base\User::find(1);
if (!$user) {
    echo "USER_NOT_FOUND";
    exit(1);
}
$token = $user->createToken('debug')->plainTextToken;
echo $token;
