<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Seed the hierarchical area structure:
     * SECRETARIA DE PLANIFICACION (sempladima)
     *   DIRECCION DE ORDENAMIENTO TERRITORIAL (dot)
     *     DEPARTAMENTO DE CARTOGRAFIA (carto)
     *     DEPARTAMENTO DE TAES (taes) -> owns its own sequence (ci.taes.XXXX)
     * OTRO (otro, root) -> default destination area for unregistered destinations
     */
    public function run(): void
    {
        $this->upsertArea('sempladima', 'SECRETARIA DE PLANIFICACION');

        $this->upsertArea('dot', 'DIRECCION DE ORDENAMIENTO TERRITORIAL', 'sempladima');

        $this->upsertArea('carto', 'DEPARTAMENTO DE CARTOGRAFIA', 'dot');

        $this->upsertArea('taes', 'DEPARTAMENTO DE TAES', 'dot');

        $this->upsertArea('otro', 'OTRO');
    }

    private function upsertArea(string $code, string $name, ?string $parentCode = null, ?string $numberingForCode = null): void
    {
        $parent = $parentCode !== null ? Area::where('code', $parentCode)->first() : null;
        $numbering = $numberingForCode !== null ? Area::where('code', $numberingForCode)->first() : null;

        Area::updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'parent_id' => $parent?->id,
                'numbering_area_id' => $numbering?->id,
                'is_active' => true,
            ],
        );
    }
}