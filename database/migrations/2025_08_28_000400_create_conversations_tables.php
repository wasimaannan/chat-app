<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('conversations', function (Blueprint $t) {
            $t->id();
            $t->enum('type', ['direct','group'])->default('direct');
            $t->unsignedBigInteger('created_by')->nullable();
            $t->text('title_enc')->nullable();
            $t->string('title_mac',128)->nullable();
            $t->timestamps();
            $t->index('type');
        });

        Schema::create('conversation_participants', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('conversation_id');
            $t->unsignedBigInteger('user_id');
            $t->enum('role',['member','admin'])->default('member');
            $t->unsignedBigInteger('last_read_message_id')->nullable();
            $t->timestamp('joined_at')->useCurrent();
            $t->unique(['conversation_id','user_id']);
            $t->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
        });

        if (!Schema::hasColumn('messages','conversation_id')) {
            Schema::table('messages', function (Blueprint $t) {
                $t->unsignedBigInteger('conversation_id')->nullable()->after('id');
                $t->index(['conversation_id','created_at']);
            });
        }
    }
    public function down(): void {
        if (Schema::hasColumn('messages','conversation_id')) {
            Schema::table('messages', function (Blueprint $t) {
                $t->dropIndex(['messages_conversation_id_created_at_index']);
                $t->dropColumn('conversation_id');
            });
        }
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
