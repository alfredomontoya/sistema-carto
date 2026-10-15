<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Communication;
use App\Repositories\Contracts\AreaRepository;
use Illuminate\Support\Facades\DB;

class AreaService
{
    public function __construct(
        private readonly AreaRepository $areas,
        private readonly NumberSequenceService $numbers,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tree(): array
    {
        return $this->areas->tree();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Area>
     */
    public function all()
    {
        return $this->areas->all();
    }

    public function find(string $id): ?Area
    {
        return $this->areas->find($id);
    }

    /**
     * Default area used when a communication has no registered
     * destination area. Created on demand if missing.
     */
    public function defaultArea(): Area
    {
        return $this->areas->findByCode('otro')
            ?? $this->areas->create([
                'code' => 'otro',
                'name' => 'OTRO',
                'parent_id' => null,
                'is_active' => true,
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Area
    {
        return $this->areas->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Area $area, array $data): Area
    {
        if (($data['numbering_area_id'] ?? null) === $area->id) {
            $data['numbering_area_id'] = null;
        }

        return $this->areas->update($area, $data);
    }

    /**
     * Deletes an area. Refuses if it has children, positions or
     * communications (to never lose correlative numbers).
     *
     * @return bool true when deleted, false when blocked
     */
    public function delete(Area $area): bool
    {
        if ($this->areas->hasChildren($area)) {
            return false;
        }

        if ($area->positions()->exists()) {
            return false;
        }

        if ($area->communications()->exists()) {
            return false;
        }

        $this->areas->delete($area);

        return true;
    }

    /**
     * Guard: an area cannot be moved under one of its own descendants.
     */
    public function wouldCreateCycle(Area $area, ?string $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        if ($newParentId === $area->id) {
            return true;
        }

        return in_array($newParentId, $this->areas->descendantIds($area), true);
    }

    /**
     * @return array<int, array{area_id: string, area_name: string, area_code: string, types: array<int, array{type_id: string, code: string, name: string, current: int, issued_max: int}>}>
     */
    public function numberingOverview(int $year, ?string $search = null): array
    {
        return $this->numbers->overview($year, $search);
    }

    /**
     * Reset the numbering of an area back to zero for the current year.
     * When no type ids are given, every communication type is reset.
     * Refuses when numbers were already issued in the selected scope
     * (they could duplicate correlatives), unless forced.
     *
     * @param  array<int, string>  $communicationTypeIds
     * @return bool true when reset, false when blocked
     */
    public function resetNumbering(Area $area, array $communicationTypeIds = [], bool $force = false): bool
    {
        $year = now()->year;

        return DB::transaction(function () use ($area, $communicationTypeIds, $year, $force): bool {
            $issuedQuery = Communication::where('area_id', $area->id)
                ->where('year', $year);

            if ($communicationTypeIds !== []) {
                $issuedQuery->whereIn('communication_type_id', $communicationTypeIds);
            }

            $issuedMax = (int) $issuedQuery->lockForUpdate()->max('sequence');

            if ($issuedMax > 0 && ! $force) {
                return false;
            }

            $this->numbers->resetForYear($area, $year, $communicationTypeIds);

            return true;
        });
    }

    /**
     * Set the numbering of an area/type to an explicit value for the current
     * year. Refuses when the value is at or below the highest sequence
     * already issued (it would duplicate correlatives), unless forced.
     *
     * @return bool true when set, false when blocked
     */
    public function setNumbering(Area $area, string $communicationTypeId, int $value, bool $force = false): bool
    {
        $year = now()->year;

        return DB::transaction(function () use ($area, $communicationTypeId, $value, $year, $force): bool {
            $issuedMax = (int) Communication::where('area_id', $area->id)
                ->where('year', $year)
                ->where('communication_type_id', $communicationTypeId)
                ->lockForUpdate()
                ->max('sequence');

            if ($issuedMax > 0 && $value <= $issuedMax && ! $force) {
                return false;
            }

            $this->numbers->setSequence($area, $communicationTypeId, $year, $value);

            return true;
        });
    }
}
