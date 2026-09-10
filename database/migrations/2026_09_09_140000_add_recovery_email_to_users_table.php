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
        Schema::table('users', function (Blueprint $table) {
            $table->string('recovery_email')->nullable()->unique();
            $table->timestamp('recovery_email_verified_at')->nullable();
        });

        Schema::create('password_recovery_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_recovery_tokens');

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['recovery_email']);
            $table->dropColumn(['recovery_email', 'recovery_email_verified_at']);
        });
    }
};
