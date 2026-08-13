<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'phone',
    'address',
    'avatar_kind',
    'avatar_value',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasUuids, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * All position assignments (history), latest first.
     *
     * @return BelongsToMany<Position, $this>
     */
    public function positionAssignments(): BelongsToMany
    {
        return $this->belongsToMany(Position::class, 'position_user')
            ->withPivot('started_at', 'ended_at')
            ->withTimestamps();
    }

    /**
     * Current active position assignment (the one without ended_at).
     *
     * @return HasOne<PositionAssignment, $this>
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(PositionAssignment::class)->whereNull('ended_at');
    }

    /**
     * The current position (puesto) of the user.
     *
     * @return HasOneThrough<Position, $this>
     */
    public function currentPosition(): HasOneThrough
    {
        return $this->hasOneThrough(
            Position::class,
            PositionAssignment::class,
            'user_id',
            'id',
            'id',
            'position_id',
        )->whereNull('position_user.ended_at');
    }

    /**
     * The username (the part before the "@" of the email).
     */
    protected function username(): Attribute
    {
        return Attribute::get(fn () => Str::before($this->email, '@'));
    }

    /**
     * The current area of the user, derived from the current position.
     * Load via `currentAssignment.position.area` to avoid N+1.
     */
    protected function currentArea(): Attribute
    {
        return Attribute::get(fn () => $this->currentAssignment?->position?->area);
    }

    /**
     * All past + current position assignments.
     *
     * @return HasMany<PositionAssignment, $this>
     */
    public function assignmentHistory(): HasMany
    {
        return $this->hasMany(PositionAssignment::class);
    }

    /**
     * Communications created by the user.
     *
     * @return HasMany<Communication, $this>
     */
    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class, 'user_id');
    }

    /**
     * Communications addressed to the user.
     *
     * @return HasMany<Communication, $this>
     */
    public function recipientCommunications(): HasMany
    {
        return $this->hasMany(Communication::class, 'recipient_user_id');
    }

    /**
     * Resolve the displayed avatar for the user.
     */
    public function avatarUrl(): ?string
    {
        if ($this->avatar_kind === 'upload' && $this->avatar_value) {
            return asset('storage/avatars/'.$this->avatar_value);
        }

        if ($this->avatar_kind === 'gallery' && $this->avatar_value) {
            return asset('storage/avatars/gallery/'.$this->avatar_value);
        }

        return null;
    }
}
