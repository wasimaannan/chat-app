<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('key_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->longText('public_key');
            $table->longText('private_key_encrypted');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users','key_pair_id')) {
                $table->foreignId('key_pair_id')->nullable()->after('id')->constrained('key_pairs');
            }
            if (!Schema::hasColumn('users','wrapped_userinfo_key')) {
                $table->longText('wrapped_userinfo_key')->nullable()->after('data_mac');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users','key_pair_id')) {
                $table->dropConstrainedForeignId('key_pair_id');
            }
            if (Schema::hasColumn('users','wrapped_userinfo_key')) {
                $table->dropColumn('wrapped_userinfo_key');
            }
        });
        Schema::dropIfExists('key_pairs');
    }
};
