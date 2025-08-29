<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts','wrapped_key')) { $table->longText('wrapped_key')->nullable(); }
            if (!Schema::hasColumn('posts','iv')) { $table->string('iv', 32)->nullable(); }
            if (!Schema::hasColumn('posts','tag')) { $table->string('tag', 64)->nullable(); }
        });
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages','wrapped_key')) { $table->longText('wrapped_key')->nullable(); }
            if (!Schema::hasColumn('messages','iv')) { $table->string('iv', 32)->nullable(); }
            if (!Schema::hasColumn('messages','tag')) { $table->string('tag', 64)->nullable(); }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts','wrapped_key')) { $table->dropColumn('wrapped_key'); }
            if (Schema::hasColumn('posts','iv')) { $table->dropColumn('iv'); }
            if (Schema::hasColumn('posts','tag')) { $table->dropColumn('tag'); }
        });
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages','wrapped_key')) { $table->dropColumn('wrapped_key'); }
            if (Schema::hasColumn('messages','iv')) { $table->dropColumn('iv'); }
            if (Schema::hasColumn('messages','tag')) { $table->dropColumn('tag'); }
        });
    }
};
