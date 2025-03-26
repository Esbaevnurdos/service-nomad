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
    $table->string('name'); // User's name (Required)
    $table->string('phone')->unique(); // Unique phone number (Required)
    $table->enum('role', ['passenger', 'driver', 'admin'])->default('passenger'); // User role
    $table->string('otp')->nullable(); // OTP code (Optional)
    $table->timestamp('otp_expires_at')->nullable(); // OTP expiry time (Optional)
    $table->decimal('latitude', 10, 7)->nullable(); // Latitude (Optional)
    $table->decimal('longitude', 10, 7)->nullable(); // Longitude (Optional)
    $table->boolean('is_available')->default(false); // Availability (Default: false)
    $table->timestamps(); // created_at & updated_at
});


        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
