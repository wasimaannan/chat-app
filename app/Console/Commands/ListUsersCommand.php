<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ListUsersCommand extends Command
{
    protected $signature = 'security:list-users {--limit=50 : Limit output rows} {--decrypt : Decrypt sensitive fields (email)}';
    protected $description = 'List users with id, (optionally decrypted) email, key pair status.';

    public function handle(): int
    {
    $limit = (int)$this->option('limit');
    $decrypt = (bool)$this->option('decrypt');
        $headers = ['ID','Email','Has Key','Key Version','Revoked?'];
        $rows = [];
        User::with('keyPair')->orderBy('id')->limit($limit)->get()->each(function($u) use (&$rows,$decrypt) {
            $emailDisplay = $u->email;
            if ($decrypt) {
                try {
                    $emailDisplay = $u->getDecryptedData()['email'] ?? '[ENCRYPTED]';
                } catch (\Throwable $e) {
                    $emailDisplay = '[ENCRYPTED]';
                }
            }
            $rows[] = [
                $u->id,
                $emailDisplay,
                $u->keyPair? 'yes':'no',
                $u->keyPair? $u->keyPair->key_version: '-',
                ($u->keyPair && $u->keyPair->revoked_at)? 'yes':'no'
            ];
        });
        $this->table($headers, $rows);
        $this->info('Total users: '.User::count());
        return 0;
    }
}
