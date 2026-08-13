<?php

namespace App\Models;

use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'area_id',
    'name',
    'code',
    'is_active',
    'sort_order',
])]
class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasFactory, HasUuids;

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PositionAssignment::class);
    }

    /**
     * All users ever assigned to this position (history).
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'position_user')
            ->withPivot('started_at', 'ended_at')
            ->withTimestamps();
    }
}
