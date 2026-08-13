<?php

namespace App\Services;

use App\Models\Position;
use App\Repositories\Contracts\PositionRepository;
use Illuminate\Database\Eloquent\Collection;

class PositionService
{
    public function __construct(
        private readonly PositionRepository $positions,
    ) {}

    public function find(string $id): ?Position
    {
        return $this->positions->find($id);
    }

    /**
     * @return Collection<int, Position>
     */
    public function forArea(string $areaId): Collection
    {
        return $this->positions->forArea($areaId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Position
    {
        return $this->positions->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Position $position, array $data): Position
    {
        return $this->positions->update($position, $data);
    }

    /**
     * Deletes a position. Refuses if it has assignments (history kept).
     *
     * @return bool true when deleted, false when blocked
     */
    public function delete(Position $position): bool
    {
        if ($position->assignments()->exists()) {
            return false;
        }

        $this->positions->delete($position);

        return true;
    }
}
