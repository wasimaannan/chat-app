<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Services\EncryptionService;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_hash')) {
                $table->string('email_hash', 64)->nullable()->unique()->after('email');
            }
        });

        // Backfill existing users with email_hash
        try {
            $encryption = app(EncryptionService::class);
            User::chunk(50, function($users) use ($encryption) {
                foreach ($users as $user) {
                    if (empty($user->email_hash) && !empty($user->email)) {
                        try {
                            $decrypted = $encryption->decrypt($user->email, 'user_info_email');
                            $user->email_hash = hash('sha256', strtolower(trim($decrypted)));
                            // Avoid triggering MAC update separately for now
                            $user->timestamps = false;
                            $user->save();
                        } catch (\Throwable $e) {
                            // Skip if cannot decrypt
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            \Log::warning('Failed to backfill email_hash', ['error' => $e->getMessage()]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email_hash')) {
                $table->dropUnique(['email_hash']);
                $table->dropColumn('email_hash');
            }
        });
    }
};
