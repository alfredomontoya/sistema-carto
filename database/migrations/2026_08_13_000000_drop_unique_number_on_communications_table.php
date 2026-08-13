<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow duplicate correlative numbers after a forced numbering reset.
     */
    public function up(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dropUnique(['number']);
            $table->index(['number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dropIndex(['number']);
            $table->unique(['number']);
        });
    }
};
