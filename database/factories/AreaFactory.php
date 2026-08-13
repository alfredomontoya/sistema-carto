<?php

namespace Database\Factories;

use App\Models\Area;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    protected $model = Area::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'code' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'name' => Str::upper($name),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function childOf(Area $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
