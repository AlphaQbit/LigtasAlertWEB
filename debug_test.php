<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::factory()->create();
$alert = App\Models\Alert::factory()->create(['user_id' => $user->id]);

echo "User ID: " . $user->id . PHP_EOL;
echo "Alert user_id: " . $alert->user_id . PHP_EOL;
echo "Match: " . ($alert->user_id === $user->id ? "yes" : "no") . PHP_EOL;