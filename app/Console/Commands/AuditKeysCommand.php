<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\KeyPair;

class AuditKeysCommand extends Command
{
    protected $signature = 'security:audit-keys {--fix-missing : Generate keys for users missing them}';
    protected $description = 'Audit user asymmetric key pairs for completeness and uniqueness';

    public function handle(): int
    {
        $fix = (bool)$this->option('fix-missing');
        $missing = 0; $fixed = 0;
        $this->info('Auditing key pairs...');
        User::with('keyPair')->chunk(100, function($users) use (&$missing,&$fixed,$fix) {
            foreach ($users as $u) {
                if (!$u->keyPair) {
                    $missing++;
                    $this->warn("User {$u->id} missing key pair");
                    if ($fix) {
                        app(\App\Services\KeyManagementService::class)->ensureUserKeyPair($u);
                        $fixed++;
                        $this->line("  -> generated key pair");
                    }
                }
            }
        });

        $dupes = KeyPair::select('fingerprint')
            ->whereNotNull('fingerprint')
            ->groupBy('fingerprint')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('fingerprint');

        if ($dupes->count()) {
            $this->error('Duplicate fingerprints detected:');
            foreach ($dupes as $fp) {
                $ids = KeyPair::where('fingerprint',$fp)->pluck('id')->implode(',');
                $this->line("  $fp => KeyPair IDs: $ids");
            }
        } else {
            $this->info('No duplicate fingerprints.');
        }

        $this->line("Users without key pair: $missing");
        if ($fix) { $this->line("Users fixed: $fixed"); }
        $this->info('Audit complete.');
        return 0;
    }
}
