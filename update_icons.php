<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Update category icons
$categories = DB::table('news_categories')->whereNotNull('icon')->get();

foreach ($categories as $category) {
    $filename = basename($category->icon);
    $newIcon = '/storage/news_categories/' . $filename;
    
    DB::table('news_categories')
        ->where('id', $category->id)
        ->update(['icon' => $newIcon]);
    
    echo "Updated category '{$category->name}': {$newIcon}\n";
}

echo "\nTotal updated: " . count($categories) . " categories\n";
