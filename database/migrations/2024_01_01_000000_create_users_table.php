<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->text('name'); // Encrypted
            $table->text('email'); // Encrypted
            $table->text('phone')->nullable(); // Encrypted
            $table->text('address')->nullable(); // Encrypted
            $table->text('date_of_birth')->nullable(); // Encrypted
            $table->string('password_hash'); // Hashed with salt
            $table->string('password_salt'); // Salt for password
            $table->text('data_mac')->nullable(); // MAC for data integrity
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            
            // Index for performance (though searching encrypted data is limited)
            $table->index('created_at');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
