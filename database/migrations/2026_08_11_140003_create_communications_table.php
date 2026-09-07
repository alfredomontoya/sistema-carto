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
        Schema::create('communications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 10);
            $table->string('number')->index();
            $table->integer('year');
            $table->unsignedBigInteger('sequence');
            $table->foreignUuid('area_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('position_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference');
            $table->string('recipient_name');
            $table->string('recipient_position')->nullable();
            $table->foreignUuid('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('activo');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->timestamps();

            $table->index(['area_id', 'year', 'type']);
            $table->index(['user_id']);
            $table->index(['position_id']);
            $table->index(['recipient_name']);
            $table->index(['status']);
            $table->index(['created_at']);
            $table->index(['status', 'year', 'area_id', 'created_at'], 'comms_dashboard_area_idx');
            $table->index(['status', 'year', 'user_id', 'created_at'], 'comms_dashboard_user_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communications');
    }
};
