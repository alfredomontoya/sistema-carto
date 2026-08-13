<?php

namespace Database\Factories;

use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PositionAssignment>
 */
class PositionAssignmentFactory extends Factory
{
    protected $model = PositionAssignment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'position_id' => Position::factory(),
            'user_id' => User::factory(),
            'started_at' => now(),
            'ended_at' => null,
        ];
    }
}
