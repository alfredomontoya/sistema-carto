<?php

namespace App\Models;

use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'parent_id',
    'numbering_area_id',
    'code',
    'name',
    'description',
    'is_active',
    'reset_annually',
])]
class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory, HasUuids;

    protected $casts = [
        'is_active' => 'boolean',
        'reset_annually' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'parent_id');
    }

    public function numberingArea(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'numbering_area_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Area::class, 'parent_id')->orderBy('name');
    }

    /**
     * The full ancestor path of the area, e.g. "CARTOGRAFIA / JEFE".
     */
    public function getPathAttribute(): string
    {
        $segments = [$this->name];
        $parent = $this->parent;

        while ($parent !== null) {
            array_unshift($segments, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' / ', $segments);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class)->orderBy('sort_order')->orderBy('name');
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }

    /**
     * Build a nested tree of areas (id => children array).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function tree(?string $parentId = null): array
    {
        $areas = static::query()
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $positions = Position::query()
            ->with(['assignments' => fn ($query) => $query->whereNull('ended_at')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('area_id');

        $tree = [];
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
}
