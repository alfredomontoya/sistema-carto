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
}
