<?php

namespace App\Models;

use Database\Factories\CommunicationTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'description',
    'digits',
    'format_template',
    'reset_annually',
    'is_active',
    'sort_order',
])]
class CommunicationType extends Model
{
    /** @use HasFactory<CommunicationTypeFactory> */
    use HasFactory, HasUuids;

    protected $table = 'type_communications';

    protected $casts = [
        'digits' => 'integer',
        'reset_annually' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class, 'communication_type_id');
    }

    public function counters(): HasMany
    {
        return $this->hasMany(AreaNumberCounter::class, 'communication_type_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
