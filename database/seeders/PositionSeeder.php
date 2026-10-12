<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Seed the default positions (puestos) for every area.
     * SISTEMAS has its own set: JEFE, SOPORTE, SECRETARIA.
     */
    public function run(): void
    {
        $default = [
            ['name' => 'JEFE', 'code' => 'jefe'],
            ['name' => 'TECNICO', 'code' => 'tecnico'],
            ['name' => 'ABOGADO', 'code' => 'abogado'],
            ['name' => 'SECRETARIA', 'code' => 'secretaria'],
            ['name' => 'ASISTENTE', 'code' => 'asistente'],
        ];

        $sistemas = [
            ['name' => 'JEFE', 'code' => 'jefe'],
            ['name' => 'SOPORTE', 'code' => 'soporte'],
            ['name' => 'SECRETARIA', 'code' => 'secretaria'],
        ];

        foreach (Area::all() as $area) {
            $positions = $area->code === 'sistemas' ? $sistemas : $default;

            foreach ($positions as $index => $position) {
                Position::updateOrCreate(
                    ['area_id' => $area->id, 'code' => $position['code']],
                    [
                        'name' => $position['name'],
                        'is_active' => true,
                        'sort_order' => $index,
                    ],
                );
            }
        }
    }
}