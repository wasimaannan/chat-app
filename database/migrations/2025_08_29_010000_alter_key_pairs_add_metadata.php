<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('key_pairs')) { return; }

        // Add new metadata columns if missing
        Schema::table('key_pairs', function (Blueprint $table) {
            if (!Schema::hasColumn('key_pairs','fingerprint')) { $table->string('fingerprint', 64)->nullable()->after('private_key_encrypted'); }
            if (!Schema::hasColumn('key_pairs','algorithm')) { $table->string('algorithm',16)->default('RSA')->after('fingerprint'); }
            if (!Schema::hasColumn('key_pairs','bits')) { $table->smallInteger('bits')->default(2048)->after('algorithm'); }
            if (!Schema::hasColumn('key_pairs','key_version')) { $table->unsignedSmallInteger('key_version')->default(1)->after('bits'); }
        });

        // Add unique indexes (ignore errors if already exist)
        try {
            Schema::table('key_pairs', function (Blueprint $table) {
                $table->unique('fingerprint','key_pairs_fingerprint_unique');
            });
        } catch (\Throwable $e) { /* index may already exist */ }
        try {
            Schema::table('key_pairs', function (Blueprint $table) {
                $table->unique('user_id','key_pairs_user_unique');
            });
        } catch (\Throwable $e) { /* index may already exist */ }
    }

    public function down(): void
    {
        if (!Schema::hasTable('key_pairs')) { return; }
        Schema::table('key_pairs', function (Blueprint $table) {
            if (Schema::hasColumn('key_pairs','fingerprint')) { @ $table->dropUnique('key_pairs_fingerprint_unique'); $table->dropColumn('fingerprint'); }
            if (Schema::hasColumn('key_pairs','algorithm')) { $table->dropColumn('algorithm'); }
            if (Schema::hasColumn('key_pairs','bits')) { $table->dropColumn('bits'); }
            if (Schema::hasColumn('key_pairs','key_version')) { $table->dropColumn('key_version'); }
            if (Schema::hasColumn('key_pairs','user_id')) { @ $table->dropUnique('key_pairs_user_unique'); }
        });
    }
};
