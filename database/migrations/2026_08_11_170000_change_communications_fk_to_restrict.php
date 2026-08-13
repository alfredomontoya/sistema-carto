<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Protect correlative numbers: deleting a user or area must not
     * cascade-delete its communications (and burn their sequences).
     * Restrict instead.
     */
    public function up(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['area_id']);
        });

        Schema::table('communications', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('area_id')->references('id')->on('areas')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['area_id']);
        });

        Schema::table('communications', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('area_id')->references('id')->on('areas')->cascadeOnDelete();
        });
    }
};
