<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow per-area control over whether the numbering resets each year.
     * Default true: the sequence restarts every year (ci.carto.0001/2027).
     */
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->boolean('reset_annually')->default(true)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn('reset_annually');
        });
    }
};
