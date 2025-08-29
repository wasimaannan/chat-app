<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\KeyManagementService;

class RotateKeysCommand extends Command
{
    protected $signature = 'security:rotate-keys {user_id? : Rotate a single user key} {--email= : Locate user by email instead of ID} {--all : Rotate all users} {--revoke : Revoke current without generating new} {--no-rewrap : Skip data rewrap}';
    protected $description = 'Rotate or revoke user asymmetric key pairs.';

    protected function configure()
    {
        parent::configure();
        $this->addOption('no-rewrap');
    }

    public function handle(): int
    {
        $kms = app(KeyManagementService::class);
        $userId = $this->argument('user_id');
        $email = $this->option('email');
        $all = (bool)$this->option('all');
        $revoke = (bool)$this->option('revoke');
        $noRewrap = $this->option('no-rewrap');

        if (($all && ($userId || $email)) || ($userId && $email)) {
            $this->error('Use only one of: user_id, --email, or --all.');
            return 1;
        }
        if (!$all && !$userId && !$email) {
            $this->error('Specify user_id, --email, or use --all.');
            return 1;
        }

        if ($all) {
            $count = User::count();
            if ($count === 0) { $this->warn('No users found.'); return 0; }
            $bar = $this->output->createProgressBar($count);
            $bar->start();
            User::chunk(100, function($users) use ($kms,$bar,$revoke,$noRewrap) {
                foreach ($users as $u) {
                    if ($revoke) { $kms->revokeUserKeyPair($u); } else { $kms->rotateUserKeyPair($u, !$noRewrap); }
                    $bar->advance();
                }
            });
            $bar->finish(); $this->newLine();
            $this->info(($revoke?'Revoked':'Rotated').' keys for all users.');
            return 0;
        }

        $user = null;
        if ($userId) { $user = User::find($userId); }
        elseif ($email) { $user = User::where('email',$email)->first(); }

        if (!$user) {
            $this->error('User not found (checked '.($userId?"id=$userId":"email=$email").'). Use php artisan security:list-users to view existing users.');
            return 1;
        }

        if ($revoke) { $kms->revokeUserKeyPair($user); $this->info('Revoked key pair for user '.$user->id); }
        else { $kp = $kms->rotateUserKeyPair($user, !$noRewrap); $this->info('Rotated key pair to version '.$kp->key_version.' for user '.$user->id); }
        return 0;
    }
}
