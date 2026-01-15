<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "Checking users in database...\n\n";

$users = User::all(['id', 'name', 'email', 'role']);

if ($users->isEmpty()) {
    echo "❌ No users found in database!\n";
    echo "\nYou need to create a user. Run:\n";
    echo "php artisan tinker\n";
    echo "Then execute:\n";
    echo "User::create(['name' => 'Admin', 'email' => 'idemosp@gmail.com', 'password' => Hash::make('Segura123**'), 'role' => 'admin']);\n";
} else {
    echo "✓ Found " . $users->count() . " user(s):\n\n";
    foreach ($users as $user) {
        echo "ID: {$user->id} | Email: {$user->email} | Name: {$user->name} | Role: {$user->role}\n";
    }

    echo "\n\nChecking specific user: idemosp@gmail.com\n";
    $specificUser = User::where('email', 'idemosp@gmail.com')->first();

    if ($specificUser) {
        echo "✓ User found!\n";
        echo "Testing password hash...\n";
        $passwordCheck = \Illuminate\Support\Facades\Hash::check('Segura123**', $specificUser->password);
        echo "Password 'Segura123**' matches: " . ($passwordCheck ? "✓ YES" : "❌ NO") . "\n";
    } else {
        echo "❌ User with email 'idemosp@gmail.com' not found!\n";
    }
}
