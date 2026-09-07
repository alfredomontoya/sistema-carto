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
        Schema::table('communications', function (Blueprint $table) {
            $table->foreignUuid('area_destino_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('area_destino_nombre')->nullable();

            $table->index(['area_destino_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->dropIndex(['area_destino_id']);
            $table->dropConstrainedForeignId('area_destino_id');
            $table->dropColumn('area_destino_nombre');
        });
    }
};
