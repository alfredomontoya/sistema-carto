<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Replace the legacy string `communications.type` ('ci'/'of') with a
     * `communication_type_id` FK to `type_communications`.
     *
     * Existing distinct codes are derived into types on the fly (no fixed
     * seeds): 'ci' -> Comunicación interna, 'of' -> Oficio externo, any
     * other code keeps its own name.
     */
    public function up(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->uuid('communication_type_id')->nullable()->after('id');
            $table->index(['area_id', 'year', 'communication_type_id'], 'comms_area_year_ctype_idx');
            $table->index(['area_id', 'year', 'communication_type_id', 'sequence'], 'comms_area_year_ctype_seq_idx');

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('communication_type_id', 'comms_ctype_id_fk')
                    ->references('id')
                    ->on('type_communications')
                    ->restrictOnDelete();
            }
        });

        $this->backfill();

        Schema::table('communications', function (Blueprint $table) {
            $table->dropIndex(['area_id', 'year', 'type']);
            $table->dropIndex('comms_area_year_type_seq_idx');
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            $table->string('type', 10)->nullable()->after('communication_type_id');
        });

        DB::table('communications')
            ->join('type_communications', 'type_communications.id', '=', 'communications.communication_type_id')
            ->update(['communications.type' => DB::raw('type_communications.code')]);

        Schema::table('communications', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('comms_ctype_id_fk');
            }

            $table->dropIndex('comms_area_year_ctype_idx');
            $table->dropIndex('comms_area_year_ctype_seq_idx');
            $table->dropColumn('communication_type_id');

            $table->index(['area_id', 'year', 'type']);
            $table->index(['area_id', 'year', 'type', 'sequence'], 'comms_area_year_type_seq_idx');
        });
    }

    private function backfill(): void
    {
        $codes = DB::table('communications')
            ->select('type')
            ->distinct()
            ->pluck('type')
            ->filter()
            ->values();

        $sort = 0;
        $ids = [];
        foreach ($codes as $code) {
            $existing = DB::table('type_communications')->where('code', $code)->value('id');

            if ($existing !== null) {
                $ids[$code] = $existing;
                continue;
            }

            $id = (string) Str::orderedUuid();
            DB::table('type_communications')->insert([
                'id' => $id,
                'code' => $code,
                'name' => match ($code) {
                    'ci' => 'Comunicación interna',
                    'of' => 'Oficio externo',
                    default => Str::upper($code),
                },
                'description' => null,
                'digits' => 4,
                'format_template' => null,
                'reset_annually' => true,
                'is_active' => true,
                'sort_order' => $sort++,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $ids[$code] = $id;
        }

        foreach ($ids as $code => $id) {
            DB::table('communications')->where('type', $code)->update(['communication_type_id' => $id]);
        }
    }
};
