<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Communication;
use App\Models\CommunicationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Communication>
 */
class CommunicationFactory extends Factory
{
    protected $model = Communication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $area = Area::factory()->create();
        $year = now()->year;
        $type = CommunicationType::query()->inRandomOrder()->first()
            ?? CommunicationType::factory()->create(['code' => 'ci', 'name' => 'Comunicación interna']);
        $destino = $type->code === 'ci' || fake()->boolean()
            ? Area::inRandomOrder()->first()
            : null;

        return [
            'communication_type_id' => $type->id,
            'number' => $type->code.'.'.strtolower($area->code).'.0001/'.$year,
            'year' => $year,
            'sequence' => 1,
            'area_id' => $area->id,
            'area_destino_id' => $destino?->id,
            'area_destino_nombre' => $destino?->name,
            'user_id' => User::factory(),
            'reference' => fake()->sentence(),
            'recipient_name' => fake()->name(),
            'recipient_position' => fake()->jobTitle(),
            'recipient_user_id' => null,
            'status' => Communication::STATUS_ACTIVE,
            'file_path' => null,
            'file_name' => null,
        ];
    }
}
