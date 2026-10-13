<?php

namespace App\Repositories\Eloquent;

use App\Models\Area;
use App\Models\AreaNumberCounter;
use App\Models\Communication;
use App\Models\CommunicationType;
use App\Repositories\Contracts\NumberCounterRepository;
use Illuminate\Support\Facades\DB;

class EloquentNumberCounterRepository implements NumberCounterRepository
{
    public function current(string $areaId, string $communicationTypeId, int $year, bool $resetAnnually = true): int
    {
        $counter = AreaNumberCounter::where('area_id', $areaId)
            ->where('communication_type_id', $communicationTypeId)
            ->where('year', $year)
            ->first();

        if ($counter !== null) {
            return (int) $counter->last_sequence;
        }

        if (! $resetAnnually) {
            return (int) AreaNumberCounter::where('area_id', $areaId)
                ->where('communication_type_id', $communicationTypeId)
                ->max('last_sequence');
        }

        return 0;
    }

    public function incrementAndGet(string $areaId, string $communicationTypeId, int $year, bool $resetAnnually = true): int
    {
        return DB::transaction(function () use ($areaId, $communicationTypeId, $year, $resetAnnually): int {
            $counter = AreaNumberCounter::where('area_id', $areaId)
                ->where('communication_type_id', $communicationTypeId)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($counter === null) {
                $initial = 0;

                if (! $resetAnnually) {
                    $initial = (int) AreaNumberCounter::where('area_id', $areaId)
                        ->where('communication_type_id', $communicationTypeId)
                        ->max('last_sequence');
                }

                $counter = AreaNumberCounter::create([
                    'area_id' => $areaId,
                    'communication_type_id' => $communicationTypeId,
                    'year' => $year,
                    'last_sequence' => $initial,
                ]);
            }

            $counter->last_sequence += 1;
            $counter->save();

            return $counter->last_sequence;
        });
    }

    public function resetForArea(string $areaId, int $year, array $communicationTypeIds = []): void
    {
        $query = AreaNumberCounter::where('area_id', $areaId)
            ->where('year', $year);

        if ($communicationTypeIds !== []) {
            $query->whereIn('communication_type_id', $communicationTypeIds);
        }

        $query->update(['last_sequence' => 0]);

        $typeIds = $communicationTypeIds !== []
            ? $communicationTypeIds
            : CommunicationType::pluck('id')->all();

        foreach ($typeIds as $typeId) {
            AreaNumberCounter::updateOrCreate(
                ['area_id' => $areaId, 'year' => $year, 'communication_type_id' => $typeId],
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
        $areasQuery = Area::query()->orderBy('name');

        if ($search !== null && trim($search) !== '') {
            $areasQuery->where(function ($query) use ($search): void {
                $query->where('areas.name', 'like', "%{$search}%")
                    ->orWhere('areas.code', 'like', "%{$search}%");
            });
        }

        $areas = $areasQuery->get(['id', 'name', 'code']);
        $types = CommunicationType::ordered()->get(['id', 'code', 'name']);

        if ($areas->isEmpty()) {
            return [];
        }

        $areaIds = $areas->pluck('id')->all();

        $counters = AreaNumberCounter::where('year', $year)
            ->whereIn('area_id', $areaIds)
            ->get(['area_id', 'communication_type_id', 'last_sequence'])
            ->keyBy(fn ($row) => $row->area_id.'|'.$row->communication_type_id);

        $issued = Communication::where('year', $year)
            ->where('status', Communication::STATUS_ACTIVE)
            ->whereIn('area_id', $areaIds)
            ->selectRaw('area_id, communication_type_id, MAX(sequence) as max_seq')
            ->groupBy('area_id', 'communication_type_id')
            ->get()
            ->keyBy(fn ($row) => $row->area_id.'|'.$row->communication_type_id);

        $rows = $areas->map(function ($area) use ($types, $counters, $issued) {
            $byType = $types->map(fn ($type) => [
                'type_id' => (string) $type->id,
                'code' => (string) $type->code,
                'name' => (string) $type->name,
                'current' => (int) ($counters[$area->id.'|'.$type->id]->last_sequence ?? 0),
                'issued_max' => (int) ($issued[$area->id.'|'.$type->id]->max_seq ?? 0),
            ])->all();

            return [
                'area_id' => (string) $area->id,
                'area_name' => (string) $area->name,
                'area_code' => (string) $area->code,
                'types' => $byType,
            ];
        })->all();

        usort($rows, function (array $a, array $b): int {
            $maxA = max(array_map(fn ($t) => $t['current'], $a['types'] ?: [0]));
            $maxB = max(array_map(fn ($t) => $t['current'], $b['types'] ?: [0]));

            return $maxB <=> $maxA ?: strcmp($a['area_name'], $b['area_name']);
        });

        return $rows;
    }

    public function setSequence(string $areaId, string $communicationTypeId, int $year, int $value): void
    {
        AreaNumberCounter::updateOrCreate(
            ['area_id' => $areaId, 'communication_type_id' => $communicationTypeId, 'year' => $year],
            ['last_sequence' => $value],
        );
    }
}
