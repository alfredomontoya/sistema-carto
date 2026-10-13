<?php

namespace Database\Factories;

use App\Models\CommunicationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunicationType>
 */
class CommunicationTypeFactory extends Factory
{
    protected $model = CommunicationType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('??'),
            'name' => fake()->words(2, true),
            'description' => null,
            'digits' => 4,
            'format_template' => null,
            'reset_annually' => true,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
