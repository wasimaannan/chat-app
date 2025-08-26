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
Artisan::command('security:rotate-keys', function () {
    $this->info('Starting key rotation...');
    
    $keyService = app(\App\Services\KeyManagementService::class);
    $keyService->rotateKeys();
    
    $this->info('Key rotation completed successfully!');
})->purpose('Rotate encryption keys for enhanced security');

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
