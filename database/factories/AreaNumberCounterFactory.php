<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\AreaNumberCounter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AreaNumberCounter>
 */
class AreaNumberCounterFactory extends Factory
{
    protected $model = AreaNumberCounter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'year' => now()->year,
            'type' => 'ci',
            'last_sequence' => 0,
        ];
    }
}
