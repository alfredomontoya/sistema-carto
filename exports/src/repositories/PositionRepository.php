<?php

namespace App\Repositories\Contracts;

use App\Models\Position;
use Illuminate\Database\Eloquent\Collection;

interface PositionRepository
{
    public function find(string $id): ?Position;

    /**
     * @return Collection<int, Position>
     */
    public function all(): Collection;

    /**
     * @return Collection<int, Position>
     */
    public function forArea(string $areaId): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Position;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Position $position, array $data): Position;

    public function delete(Position $position): void;
}
