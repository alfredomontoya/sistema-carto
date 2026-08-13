<?php

namespace App\Repositories\Eloquent;

use App\Models\Area;
use App\Repositories\Contracts\AreaRepository;
use Illuminate\Database\Eloquent\Collection;

class EloquentAreaRepository implements AreaRepository
{
    public function all(): Collection
    {
        return Area::with('children')->orderBy('name')->get();
    }

    public function tree(): array
    {
        return Area::tree();
    }

    public function find(string $id): ?Area
    {
        return Area::with('parent')->find($id);
    }

    public function findByCode(string $code): ?Area
    {
        return Area::where('code', $code)->first();
    }

    public function create(array $data): Area
    {
        return Area::create($data);
    }

    public function update(Area $area, array $data): Area
    {
        $area->update($data);

        return $area;
    }

    public function delete(Area $area): void
    {
        $area->delete();
    }

    public function descendantIds(Area $area): array
    {
        return $this->collectDescendants($area)->pluck('id')->all();
    }

    public function hasChildren(Area $area): bool
    {
        return $area->children()->exists();
    }

    private function collectDescendants(Area $area): Collection
    {
        $descendants = new Collection;
        $stack = $area->children()->get();

        while ($stack->isNotEmpty()) {
            $descendants = $descendants->merge($stack);
            $stack = $stack->flatMap(fn (Area $child) => $child->children()->get());
        }

        return $descendants;
    }
}
