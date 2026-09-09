<?php

namespace App\Repositories\Eloquent;

use App\Models\Area;
use App\Models\Position;
use App\Repositories\Contracts\AreaRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class EloquentAreaRepository implements AreaRepository
{
    public function all(): Collection
    {
        return Area::with('children')->orderBy('name')->get();
    }

    /**
     * Build a nested tree of areas with their positions and user counts.
     *
     * @return array<int, array<string, mixed>>
     */
    public function tree(): array
    {
        $areas = Area::query()
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $positions = Position::query()
            ->with(['assignments' => fn ($query) => $query->whereNull('ended_at')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('area_id');

        $childrenMap = [];

        foreach ($areas as $area) {
            $childrenMap[$area->parent_id ?? 'root'][] = $area;
        }

        $build = function (string $key, int $depth = 0) use (&$build, &$childrenMap, $areas, $positions) {
            $nodes = [];
            foreach ($childrenMap[$key] ?? [] as $area) {
                $areaPositions = $positions[$area->id] ?? collect();

                $nodes[] = [
                    'id' => $area->id,
                    'parent_id' => $area->parent_id,
                    'numbering_area_id' => $area->numbering_area_id,
                    'numbering_area_name' => $area->numbering_area_id !== null
                        ? ($areas[$area->numbering_area_id]->name ?? null)
                        : null,
                    'code' => $area->code,
                    'name' => $area->name,
                    'description' => $area->description,
                    'is_active' => $area->is_active,
                    'reset_annually' => $area->reset_annually,
                    'depth' => $depth,
                    'position_count' => $areaPositions->count(),
                    'user_count' => $areaPositions->sum(fn (Position $position) => $position->assignments->count()),
                    'positions' => $areaPositions->map(fn (Position $position) => [
                        'id' => $position->id,
                        'name' => $position->name,
                        'code' => $position->code,
                        'is_active' => $position->is_active,
                        'sort_order' => $position->sort_order,
                        'user_count' => $position->assignments->count(),
                    ])->values(),
                    'children' => $build($area->id, $depth + 1),
                ];
            }

            return $nodes;
        };

        return $build('root');
    }

    public function find(string $id): ?Area
    {
        return Area::with('parent')->find($id);
    }

    public function findByCode(string $code): ?Area
    {
        return Area::where('code', $code)->first();
    }

    public function search(string $term, int $limit = 10): Collection
    {
        return Area::query()
            ->where('is_active', true)
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            })
            ->with('parent')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'code', 'parent_id']);
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
        $allAreas = Area::query()->get(['id', 'parent_id']);

        $childrenMap = $allAreas->groupBy('parent_id');

        $descendants = new Collection;
        $stack = $childrenMap->get($area->id, new Collection);

        while ($stack->isNotEmpty()) {
            $descendants = $descendants->merge($stack);
            $nextStack = new Collection;
            foreach ($stack as $child) {
                $nextStack = $nextStack->merge($childrenMap->get($child->id, new Collection));
            }
            $stack = $nextStack;
        }

        return $descendants;
    }
}
