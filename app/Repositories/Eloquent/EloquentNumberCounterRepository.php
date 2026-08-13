<?php

namespace App\Repositories\Eloquent;

use App\Models\AreaNumberCounter;
use App\Repositories\Contracts\NumberCounterRepository;
use Illuminate\Support\Facades\DB;

class EloquentNumberCounterRepository implements NumberCounterRepository
{
    public function current(string $areaId, string $type, int $year, bool $resetAnnually = true): int
    {
        $counter = AreaNumberCounter::where('area_id', $areaId)
            ->where('type', $type)
            ->where('year', $year)
            ->first();

        if ($counter !== null) {
            return (int) $counter->last_sequence;
        }

        if (! $resetAnnually) {
            return (int) AreaNumberCounter::where('area_id', $areaId)
                ->where('type', $type)
                ->max('last_sequence');
        }

        return 0;
    }

    public function incrementAndGet(string $areaId, string $type, int $year, bool $resetAnnually = true): int
    {
        return DB::transaction(function () use ($areaId, $type, $year, $resetAnnually): int {
            $counter = AreaNumberCounter::where('area_id', $areaId)
                ->where('type', $type)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                $initial = 0;

                if (! $resetAnnually) {
                    $initial = (int) AreaNumberCounter::where('area_id', $areaId)
                        ->where('type', $type)
                        ->max('last_sequence');
                }

                $counter = AreaNumberCounter::create([
                    'area_id' => $areaId,
                    'type' => $type,
                    'year' => $year,
                    'last_sequence' => $initial,
                ]);
            }

            $counter->last_sequence += 1;
            $counter->save();

            return $counter->last_sequence;
        });
    }

    public function resetForArea(string $areaId, int $year, array $types = []): void
    {
        $query = AreaNumberCounter::where('area_id', $areaId)
            ->where('year', $year);

        if ($types !== []) {
            $query->whereIn('type', $types);
        }

        $query->update(['last_sequence' => 0]);

        foreach (($types !== [] ? $types : ['ci', 'of']) as $type) {
            AreaNumberCounter::updateOrCreate(
                ['area_id' => $areaId, 'year' => $year, 'type' => $type],
                ['last_sequence' => 0],
            );
        }
    }

    public function resetAll(): void
    {
        AreaNumberCounter::query()->delete();
    }
}
