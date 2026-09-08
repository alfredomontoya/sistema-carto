<?php

namespace App\Repositories\Eloquent;

use App\Models\Area;
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

    public function overview(int $year, ?string $search = null): array
    {
        $query = Area::query()
            ->leftJoin('area_number_counters', function ($join) use ($year): void {
                $join->on('area_number_counters.area_id', '=', 'areas.id')
                    ->where('area_number_counters.year', '=', $year);
            })
            ->groupBy('areas.id', 'areas.name', 'areas.code')
            ->orderByDesc(DB::raw('MAX(`area_number_counters`.`last_sequence`)'))
            ->orderBy('areas.name');

        if ($search !== null && trim($search) !== '') {
            $query->where(function ($query) use ($search): void {
                $query->where('areas.name', 'like', "%{$search}%")
                    ->orWhere('areas.code', 'like', "%{$search}%");
            });
        }

        return $query->get([
            'areas.id as area_id',
            'areas.name as area_name',
            'areas.code as area_code',
            DB::raw("MAX(CASE WHEN `area_number_counters`.`type` = 'ci' THEN `area_number_counters`.`last_sequence` ELSE 0 END) as ci_current"),
            DB::raw("MAX(CASE WHEN `area_number_counters`.`type` = 'of' THEN `area_number_counters`.`last_sequence` ELSE 0 END) as of_current"),
            DB::raw("(SELECT COALESCE(MAX(`sequence`), 0) FROM `communications` WHERE `communications`.`area_id` = `areas`.`id` AND `communications`.`year` = {$year} AND `communications`.`type` = 'ci') as ci_issued_max"),
            DB::raw("(SELECT COALESCE(MAX(`sequence`), 0) FROM `communications` WHERE `communications`.`area_id` = `areas`.`id` AND `communications`.`year` = {$year} AND `communications`.`type` = 'of') as of_issued_max"),
        ])
            ->map(fn ($row) => [
                'area_id' => (string) $row->area_id,
                'area_name' => (string) $row->area_name,
                'area_code' => (string) $row->area_code,
                'ci_current' => (int) $row->ci_current,
                'of_current' => (int) $row->of_current,
                'ci_issued_max' => (int) $row->ci_issued_max,
                'of_issued_max' => (int) $row->of_issued_max,
            ])
            ->all();
    }

    public function setSequence(string $areaId, string $type, int $year, int $value): void
    {
        AreaNumberCounter::updateOrCreate(
            ['area_id' => $areaId, 'type' => $type, 'year' => $year],
            ['last_sequence' => $value],
        );
    }
}
