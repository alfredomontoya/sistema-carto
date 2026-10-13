<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the legacy string `area_number_counters.type` with a
     * `communication_type_id` FK to `type_communications`, keeping the
     * per (area, year, type) numbering granularity.
     *
     * The replacement unique index is created before dropping the legacy
     * one so MySQL can reassign the area FK to the new index.
     */
    public function up(): void
    {
        Schema::table('area_number_counters', function (Blueprint $table) {
            $table->uuid('communication_type_id')->nullable()->after('area_id');

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('communication_type_id', 'counters_ctype_id_fk')
                    ->references('id')
                    ->on('type_communications')
                    ->cascadeOnDelete();
            }
        });

        $this->backfill();

        Schema::table('area_number_counters', function (Blueprint $table) {
            $table->unique(['area_id', 'year', 'communication_type_id'], 'counters_area_year_ctype_unique');
        });

        Schema::table('area_number_counters', function (Blueprint $table) {
            // On SQLite the legacy unique must be dropped explicitly before
            // the column, otherwise the table rebuild fails.
            $table->dropUnique(['area_id', 'year', 'type']);

            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('area_number_counters', function (Blueprint $table) {
            $table->string('type', 10)->nullable()->after('communication_type_id');
        });

        DB::table('area_number_counters')
            ->join('type_communications', 'type_communications.id', '=', 'area_number_counters.communication_type_id')
            ->update(['area_number_counters.type' => DB::raw('type_communications.code')]);

        Schema::table('area_number_counters', function (Blueprint $table) {
            $table->unique(['area_id', 'year', 'type'], 'area_number_counters_area_id_year_type_unique');
        });

        Schema::table('area_number_counters', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('counters_ctype_id_fk');
            }

            $table->dropUnique('counters_area_year_ctype_unique');
            $table->dropColumn('communication_type_id');
        });
    }

    private function backfill(): void
    {
        $ids = DB::table('type_communications')->pluck('id', 'code');

        $codes = DB::table('area_number_counters')
            ->select('type')
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values();

        foreach ($codes as $code) {
            if (! isset($ids[$code])) {
                continue;
            }

            DB::table('area_number_counters')
                ->where('type', $code)
                ->update(['communication_type_id' => $ids[$code]]);
        }
    }
};
