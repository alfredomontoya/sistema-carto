<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::upper(fake()->unique()->word());

        return [
            'area_id' => Area::factory(),
            'name' => $name,
            'code' => Str::slug($name),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function inArea(Area $area): static
    {
        return $this->state(fn () => ['area_id' => $area->id]);
    }
}
