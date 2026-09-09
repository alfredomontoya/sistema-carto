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
        if ($newParentId === null || $newParentId === $area->id) {
            return true;
        }

        return in_array($newParentId, $this->areas->descendantIds($area), true);
    }

    /**
     * @return array<int, array{area_id: string, area_name: string, area_code: string, ci_current: int, of_current: int, ci_issued_max: int, of_issued_max: int}>
     */
    public function numberingOverview(int $year, ?string $search = null): array
    {
        return $this->numbers->overview($year, $search);
    }

    /**
     * Set the numbering of an area/type to an explicit value for the current
     * year. Refuses when the value is at or below the highest sequence
     * already issued (it would duplicate correlatives), unless forced.
     *
     * @return bool true when set, false when blocked
     */
    public function setNumbering(Area $area, string $type, int $value, bool $force = false): bool
    {
        $year = now()->year;

        return DB::transaction(function () use ($area, $type, $value, $year, $force): bool {
            $issuedMax = (int) Communication::where('area_id', $area->id)
                ->where('year', $year)
                ->where('type', $type)
                ->lockForUpdate()
                ->max('sequence');

            if ($value <= $issuedMax && ! $force) {
                return false;
            }

            $this->numbers->setSequence($area, $type, $year, $value);

            return true;
        });
    }
}
