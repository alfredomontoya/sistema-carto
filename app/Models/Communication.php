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
    'communication_type_id',
    'number',
    'year',
    'sequence',
    'area_id',
    'area_destino_id',
    'area_destino_nombre',
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

    public const STATUS_ACTIVE = 'activo';

    public const STATUS_ANNULLED = 'anulado';

    protected $casts = [
        'year' => 'integer',
        'sequence' => 'integer',
    ];

    public function communicationType(): BelongsTo
    {
        return $this->belongsTo(CommunicationType::class, 'communication_type_id');
    }

    /**
     * Legacy reader: the type code (e.g. "ci") resolved through the
     * communication type. Kept for the PDF report and backwards
     * compatibility; not mass-assignable.
     */
    public function getTypeAttribute(): ?string
    {
        return $this->communicationType?->code;
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function areaDestino(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_destino_id');
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
            function (Builder $q, string $type): void {
                $id = CommunicationType::where('code', $type)->value('id');
                $q->where('communication_type_id', $id ?? $type);
            },
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
