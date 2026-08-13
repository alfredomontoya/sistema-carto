<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Converts legacy leaf sub-areas (used as "puestos") into positions and moves
     * area_user history into position_user, keeping the data intact.
     */
    public function up(): void
    {
        Schema::create('position_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('position_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'ended_at']);
            $table->index(['position_id', 'ended_at']);
        });

        $knownOrder = array_flip(['jefe', 'tecnico', 'abogado', 'secretaria', 'asistente']);

        $oldAreaToPosition = [];
        $childAreaIds = DB::table('areas')->whereNotNull('parent_id')->pluck('id');
        $parentAreaIds = DB::table('areas')->pluck('parent_id')->filter();

        $leafAreaIds = $childAreaIds->reject(fn ($id) => $parentAreaIds->contains($id));

        $leafAreas = DB::table('areas')->whereIn('id', $leafAreaIds)->orderBy('name')->get();
        foreach ($leafAreas as $area) {
            $positionId = (string) Str::uuid();
            $oldAreaToPosition[$area->id] = $positionId;

            DB::table('positions')->insert([
                'id' => $positionId,
                'area_id' => $area->parent_id,
                'name' => $area->name,
                'code' => $area->code,
                'is_active' => $area->is_active,
                'sort_order' => $knownOrder[$area->code] ?? 999,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $firstPositionForArea = static function (string $areaId): ?string {
            return DB::table('positions')
                ->where('area_id', $areaId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->value('id');
        };

        $assignments = DB::table('area_user')->orderBy('started_at')->get();
        foreach ($assignments as $assignment) {
            $positionId = $oldAreaToPosition[$assignment->area_id] ?? $firstPositionForArea($assignment->area_id);
            if ($positionId === null) {
                continue;
            }

            DB::table('position_user')->insert([
                'id' => (string) Str::uuid(),
                'position_id' => $positionId,
                'user_id' => $assignment->user_id,
                'started_at' => $assignment->started_at,
                'ended_at' => $assignment->ended_at,
                'created_at' => $assignment->created_at ?? now(),
                'updated_at' => now(),
            ]);
        }

        $currentPositionForUser = static function (string $userId): ?string {
            return DB::table('position_user')
                ->where('user_id', $userId)
                ->whereNull('ended_at')
                ->value('position_id');
        };

        $communications = DB::table('communications')->get();
        foreach ($communications as $communication) {
            $positionId = $oldAreaToPosition[$communication->area_id] ?? null;

            if ($positionId === null) {
                $currentPosition = $currentPositionForUser($communication->user_id);
                if ($currentPosition !== null
                    && DB::table('positions')->where('id', $currentPosition)->value('area_id') === $communication->area_id) {
                    $positionId = $currentPosition;
                }
            }

            if ($positionId === null) {
                continue;
            }

            DB::table('communications')->where('id', $communication->id)->update([
                'position_id' => $positionId,
                'area_id' => DB::table('positions')->where('id', $positionId)->value('area_id'),
            ]);
        }

        Schema::dropIfExists('area_user');

        if ($leafAreaIds->isNotEmpty()) {
            DB::table('areas')->whereIn('id', $leafAreaIds)->delete();
        }
    }

    /**
     * Reverse the migrations.
     *
     * Re-creates the legacy sub-areas and area_user history from positions data.
     */
    public function down(): void
    {
        Schema::create('area_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('area_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'ended_at']);
            $table->index(['area_id', 'ended_at']);
        });

        $positionToArea = [];

        $positions = DB::table('positions')->orderBy('name')->get();
        foreach ($positions as $position) {
            $areaId = (string) Str::uuid();
            $positionToArea[$position->id] = $areaId;

            DB::table('areas')->insert([
                'id' => $areaId,
                'parent_id' => $position->area_id,
                'code' => $position->code ?? (string) Str::uuid(),
                'name' => $position->name,
                'is_active' => $position->is_active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($positions as $position) {
            $assignments = DB::table('position_user')->where('position_id', $position->id)->get();
            foreach ($assignments as $assignment) {
                DB::table('area_user')->insert([
                    'id' => (string) Str::uuid(),
                    'area_id' => $positionToArea[$position->id],
                    'user_id' => $assignment->user_id,
                    'started_at' => $assignment->started_at,
                    'ended_at' => $assignment->ended_at,
                    'created_at' => $assignment->created_at ?? now(),
                    'updated_at' => $assignment->updated_at ?? now(),
                ]);
            }
        }

        $communications = DB::table('communications')->whereNotNull('position_id')->get();
        foreach ($communications as $communication) {
            if (isset($positionToArea[$communication->position_id])) {
                DB::table('communications')->where('id', $communication->id)->update([
                    'area_id' => $positionToArea[$communication->position_id],
                ]);
            }
        }

        Schema::dropIfExists('position_user');
        DB::table('positions')->delete();
    }
};
