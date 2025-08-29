<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Security-related console commands
// Renamed to avoid collision with class-based RotateKeysCommand
Artisan::command('security:rotate-master-keys', function () {
    $this->info('Rotating master/data symmetric keys...');
    $keyService = app(\App\Services\KeyManagementService::class);
    $keyService->rotateKeys();
    $this->info('Master/data key rotation completed. (Does not rotate per-user RSA pairs)');
})->purpose('Rotate master symmetric keys (NOT per-user RSA)');

// Hint: use --no-rewrap to skip decrypt+re-encrypt of user fields during rotation.

Artisan::command('security:verify-integrity', function () {
    $this->info('Verifying data integrity...');
    
    $users = \App\Models\User::all();
    $posts = \App\Models\Post::all();
    
    $userIntegrityIssues = 0;
    $postIntegrityIssues = 0;
    
    foreach ($users as $user) {
        if (!$user->verifyIntegrity()) {
            $userIntegrityIssues++;
            $this->warn("User ID {$user->id} has integrity issues");
        }
    }
    
    foreach ($posts as $post) {
        if (!$post->verifyIntegrity()) {
            $postIntegrityIssues++;
            $this->warn("Post ID {$post->id} has integrity issues");
        }
    }
    
    if ($userIntegrityIssues === 0 && $postIntegrityIssues === 0) {
        $this->info('All data integrity checks passed!');
    } else {
        $this->error("Found {$userIntegrityIssues} user integrity issues and {$postIntegrityIssues} post integrity issues");
    }
})->purpose('Verify data integrity using MAC verification');
