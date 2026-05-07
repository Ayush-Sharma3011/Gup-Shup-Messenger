<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Conversation;
use App\Models\User;

try {
    $userId = User::first()->_id;
    echo "UserID: " . $userId . "\n";
    
    // We override the casts property dynamically to test if that is the issue
    Conversation::resolveRelationUsing('dummy', function() {}); // Just to boot
    
    $c = Conversation::query()->where('participants', (string) $userId)->count();
    echo "With cast: $c\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
