<?php

namespace App\Models;

use Database\Factories\CommunicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'type',
    'number',
    'year',
    'sequence',
    'area_id',
    'user_id',
    'position_id',
    'reference',
    'recipient_name',
    'recipient_position',
    'recipient_user_id',
    'status',
    'file_path',
    'file_name',
])]
class Communication extends Model
{
    /** @use HasFactory<CommunicationFactory> */
    use HasFactory, HasUuids;

    public const TYPE_INTERNAL = 'ci';

    public const TYPE_EXTERNAL = 'of';

    public const STATUS_ACTIVE = 'activo';

    public const STATUS_ANNULLED = 'anulado';

    protected $casts = [
        'year' => 'integer',
        'sequence' => 'integer',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /**
     * Only active (non-annulled) records.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<self>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $query->when(
            $filters['search'] ?? null,
            fn (Builder $q, string $s) => $q->where(function (Builder $q) use ($s) {
                $q->where('number', 'like', "%{$s}%")
                    ->orWhere('recipient_name', 'like', "%{$s}%")
                    ->orWhere('reference', 'like', "%{$s}%");
            })
        );

        $query->when(
            $filters['type'] ?? null,
            fn (Builder $q, string $type) => $q->where('type', $type),
        );

        $query->when(
            isset($filters['area_id']) && $filters['area_id'] !== '' && $filters['area_id'] !== 'all',
            fn (Builder $q) => $q->where('area_id', $filters['area_id']),
        );

        $query->when(
            $filters['user_id'] ?? null,
            fn (Builder $q, string $userId) => $q->where('user_id', $userId),
        );

        $query->when(
            $filters['recipient_user_id'] ?? null,
            fn (Builder $q, string $userId) => $q->where('recipient_user_id', $userId),
        );

        $query->when(
            $filters['recipient_name'] ?? null,
            fn (Builder $q, string $name) => $q->where('recipient_name', 'like', "%{$name}%"),
        );

        $query->when(
            $filters['number'] ?? null,
            fn (Builder $q, string $number) => $q->where('number', 'like', "%{$number}%"),
        );

        $query->when(
            $filters['date_from'] ?? null,
            fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date),
        );

        $query->when(
            $filters['date_to'] ?? null,
            fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date),
        );

        $status = $filters['status'] ?? null;
        if ($status === 'activo') {
            $query->where('status', self::STATUS_ACTIVE);
        } elseif ($status === 'anulado') {
            $query->where('status', self::STATUS_ANNULLED);
        }

        return $query;
    }
}
