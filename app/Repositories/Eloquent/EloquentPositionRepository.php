<?php

namespace App\Repositories\Eloquent;

use App\Models\Position;
use App\Repositories\Contracts\PositionRepository;
use Illuminate\Database\Eloquent\Collection;

class EloquentPositionRepository implements PositionRepository
{
    public function find(string $id): ?Position
    {
        return Position::with(['area'])->find($id);
    }

    public function all(): Collection
    {
        return Position::with(['area'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function forArea(string $areaId): Collection
    {
        return Position::query()
            ->where('area_id', $areaId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Position
    {
        return Position::create($data);
    }

    public function update(Position $position, array $data): Position
    {
        $position->update($data);

        return $position;
    }

    public function delete(Position $position): void
    {
        $position->delete();
    }
}
