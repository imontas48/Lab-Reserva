<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "Updating password for idemosp@gmail.com...\n\n";

$user = User::where('email', 'idemosp@gmail.com')->first();

if ($user) {
    $user->password = Hash::make('Segura123**');
    $user->save();

    echo "✓ Password updated successfully!\n";
    echo "Email: {$user->email}\n";
    echo "New Password: Segura123**\n";
    echo "\nYou can now login with these credentials.\n";
} else {
    echo "❌ User not found!\n";
}
