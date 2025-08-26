<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure appropriate column types for MySQL (text already fine). Just make sure lengths are acceptable.
        Schema::table('users', function (Blueprint $table) {
            // If moving from sqlite, we may want indexes; email_hash already unique.
            if (Schema::hasColumn('users', 'email_hash')) {
                $table->string('email_hash', 64)->change();
            }
        });
    }

    public function down(): void
    {
        // No-op (reverting not required for sqlite)
    }
};
