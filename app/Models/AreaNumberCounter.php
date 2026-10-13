<?php

namespace App\Models;

use Database\Factories\AreaNumberCounterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'area_id',
    'communication_type_id',
    'year',
    'last_sequence',
])]
class AreaNumberCounter extends Model
{
    /** @use HasFactory<AreaNumberCounterFactory> */
    use HasFactory, HasUuids;

    protected $casts = [
        'year' => 'integer',
        'last_sequence' => 'integer',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function communicationType(): BelongsTo
    {
        return $this->belongsTo(CommunicationType::class, 'communication_type_id');
    }
}
