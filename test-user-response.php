<?php

/**
 * Quick test script to verify formatUserResponse() is working
 * 
 * Run this from terminal:
 * cd news-api
 * php test-user-response.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== Testing User Profile Image URL Generation ===\n\n";

// Get a user with a profile image
$user = User::whereNotNull('profile_image')->first();

if (!$user) {
    echo "❌ No users found with profile images!\n";
    echo "Please upload a profile image for a test user first.\n\n";
    
    // Show all users
    $allUsers = User::all();
    echo "Total users in database: " . $allUsers->count() . "\n";
    foreach ($allUsers as $u) {
        echo "  - {$u->name} ({$u->email}): profile_image = " . ($u->profile_image ?? 'NULL') . "\n";
    }
    exit(1);
}

echo "✅ Found user with profile image:\n";
echo "  ID: {$user->id}\n";
echo "  Name: {$user->name}\n";
echo "  Email: {$user->email}\n";
echo "  Profile Image Path: {$user->profile_image}\n\n";

// Test formatUserResponse method
$controller = new \App\Http\Controllers\UserController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('formatUserResponse');
$method->setAccessible(true);

$formattedUser = $method->invoke($controller, $user);

echo "=== Formatted User Response ===\n";
echo json_encode($formattedUser, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

if (isset($formattedUser['profile_image_url'])) {
    echo "✅ profile_image_url is present!\n";
    echo "   URL: {$formattedUser['profile_image_url']}\n\n";
    
    // Check if file exists
    $storagePath = storage_path('app/public/' . $user->profile_image);
    if (file_exists($storagePath)) {
        echo "✅ Image file exists at: {$storagePath}\n";
        echo "   File size: " . filesize($storagePath) . " bytes\n";
    } else {
        echo "❌ Image file NOT found at: {$storagePath}\n";
        echo "   The URL will return 404!\n";
    }
    
    // Check if public/storage symlink exists
    $symlinkPath = public_path('storage');
    if (is_link($symlinkPath)) {
        echo "✅ Storage symlink exists at: {$symlinkPath}\n";
        echo "   Points to: " . readlink($symlinkPath) . "\n";
    } else if (is_dir($symlinkPath)) {
        echo "⚠️  Storage path exists as directory (not symlink) at: {$symlinkPath}\n";
    } else {
        echo "❌ Storage symlink NOT found at: {$symlinkPath}\n";
        echo "   Run: php artisan storage:link\n";
    }
} else {
    echo "❌ profile_image_url is NOT in the response!\n";
    echo "   formatUserResponse() is not working correctly.\n";
}

echo "\n=== Test Complete ===\n";
