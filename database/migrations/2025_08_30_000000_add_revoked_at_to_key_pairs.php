<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('key_pairs')) { return; }
        Schema::table('key_pairs', function (Blueprint $table) {
            if (!Schema::hasColumn('key_pairs','revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('key_version');
                $table->index('revoked_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('key_pairs')) { return; }
        Schema::table('key_pairs', function (Blueprint $table) {
            if (Schema::hasColumn('key_pairs','revoked_at')) {
                $table->dropIndex(['revoked_at']);
                $table->dropColumn('revoked_at');
            }
        });
    }
};
