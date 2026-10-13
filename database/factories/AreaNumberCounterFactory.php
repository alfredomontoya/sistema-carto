<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\AreaNumberCounter;
use App\Models\CommunicationType;
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
            'communication_type_id' => CommunicationType::factory(),
            'year' => now()->year,
            'last_sequence' => 0,
        ];
    }
}
