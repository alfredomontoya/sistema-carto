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
        Schema::create('areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->foreignUuid('numbering_area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('reset_annually')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on('areas')
                ->nullOnDelete();

            $table->index('parent_id', 'areas_parent_id_idx');
            $table->index('numbering_area_id', 'areas_numbering_area_id_idx');
            $table->index('is_active', 'areas_is_active_idx');
            $table->index('name', 'areas_name_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
