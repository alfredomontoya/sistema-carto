<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Communication;
use App\Repositories\Contracts\AreaRepository;

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
     * Resets the area numbering for the current year. Refuses if numbers were
     * already issued this year (for the area or for areas numbering as it).
     *
     * @return bool true when reset, false when blocked
     */
    public function resetNumbering(Area $area, ?int $year = null): bool
    {
        $year ??= now()->year;

        $numberingAreaIds = Area::query()
            ->where('numbering_area_id', $area->id)
            ->pluck('id')
            ->push($area->id);

        $issued = Communication::whereIn('area_id', $numberingAreaIds)
            ->where('year', $year)
            ->exists();

        if ($issued) {
            return false;
        }

        $this->numbers->resetForYear($area, $year);

        return true;
    }
}
