<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\KeyManagementService;
use App\Services\EncryptionService;
use Throwable;

class BackfillHybridEncryption extends Command
{
    protected $signature = 'security:backfill-hybrid {--chunk=50 : Number of users per chunk} {--dry-run : Only show what would change}';
    protected $description = 'Generate RSA key pairs for existing users and migrate legacy encrypted fields to hybrid encryption (RSA+AES-GCM).';

    public function handle(): int
    {
        $chunk = (int)$this->option('chunk');
        $dry = (bool)$this->option('dry-run');
        $kms = app(KeyManagementService::class);
        $enc = app(EncryptionService::class);
        $updated = 0; $skipped = 0; $errors = 0;

        $this->info('Starting hybrid encryption backfill'.($dry ? ' (dry run)' : ''));        
        User::chunk($chunk, function($users) use (&$updated,&$skipped,&$errors,$kms,$enc,$dry) {
            foreach ($users as $u) {
                try {
                    $kms->ensureUserKeyPair($u);
                    if (!empty($u->wrapped_userinfo_key)) { $skipped++; continue; }
                    // decrypt via legacy path
                    $plain = $enc->decryptUserInfo([
                        'name'=>$u->name,
                        'email'=>$u->email,
                        'phone'=>$u->phone,
                        'address'=>$u->address,
                        'date_of_birth'=>$u->date_of_birth,
                    ]);
                    if ($dry) {
                        $this->line("Would update user #{$u->id}");
                        continue;                    
                    }
                    $u->setEncryptedData($plain);
                    $u->save();
                    $updated++;
                } catch (Throwable $t) {
                    $errors++;
                    $this->error("User #{$u->id} failed: ".$t->getMessage());
                }
            }
        });
        $this->info("Users updated: $updated, skipped: $skipped, errors: $errors");
        return $errors === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
