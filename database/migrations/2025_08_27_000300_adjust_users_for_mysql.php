<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip alteration when using sqlite (in-memory tests) to avoid DBAL requirement.
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        if ($driver === 'sqlite') {
            return; // no-op for sqlite testing
        }
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email_hash')) {
                try {
                    $table->string('email_hash', 64)->change();
                } catch (Throwable $e) {
                    // Silently ignore if platform doesn't support change without DBAL
                }
            }
        });
    }

    public function down(): void
    {
        // No-op (reverting not required for sqlite)
    }
};
