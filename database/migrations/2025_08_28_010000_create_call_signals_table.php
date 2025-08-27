<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('call_signals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('from_user_id');
            $table->string('type', 32);
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['conversation_id','id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('call_signals');
    }
};
