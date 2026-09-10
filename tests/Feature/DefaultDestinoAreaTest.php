<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Services\AreaService;
use App\Services\CommunicationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultDestinoAreaTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $area = Area::create([
            'code' => 'CARTO',
            'name' => 'DEPARTAMENTO DE CARTOGRAFIA',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $position = Position::create([
            'area_id' => $area->id,
            'code' => 'JEFE',
            'name' => 'JEFE',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = User::create([
            'name' => 'Autor',
            'email' => 'autor@carto.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('usuario');

        PositionAssignment::create([
            'user_id' => $user->id,
            'position_id' => $position->id,
            'started_at' => now(),
            'ended_at' => null,
        ]);

        return $user;
    }

    private function payload(array $extra = []): array
    {
        return [
            'type' => 'ci',
            'reference' => 'Ref',
            'recipient_name' => 'Dest',
            'recipient_position' => 'TECNICO',
            ...$extra,
        ];
    }

    public function test_default_area_is_created_as_root(): void
    {
        $otro = app(AreaService::class)->defaultArea();

        $this->assertSame('otro', $otro->code);
        $this->assertSame('OTRO', $otro->name);
        $this->assertNull($otro->parent_id);
        $this->assertSame($otro->id, app(AreaService::class)->defaultArea()->id);
    }

    public function test_empty_destino_falls_back_to_otro(): void
    {
        $service = app(CommunicationService::class);
        $otro = app(AreaService::class)->defaultArea();

        $com = $service->create($this->user(), $this->payload());

        $this->assertSame($otro->id, $com->area_destino_id);
        $this->assertSame('OTRO', $com->area_destino_nombre);
    }

    public function test_free_text_destino_keeps_text_with_otro_id(): void
    {
        $service = app(CommunicationService::class);
        $otro = app(AreaService::class)->defaultArea();

        $com = $service->create($this->user(), $this->payload(['area_destino_nombre' => 'Externa XYZ']));

        $this->assertSame($otro->id, $com->area_destino_id);
        $this->assertSame('Externa XYZ', $com->area_destino_nombre);
    }

    public function test_registered_destino_is_untouched(): void
    {
        $service = app(CommunicationService::class);
        $taes = Area::create([
            'code' => 'TAES',
            'name' => 'DEPARTAMENTO DE TAES',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $com = $service->create($this->user(), $this->payload([
            'area_destino_id' => $taes->id,
            'area_destino_nombre' => 'DEPARTAMENTO DE TAES',
        ]));

        $this->assertSame($taes->id, $com->area_destino_id);
        $this->assertSame('DEPARTAMENTO DE TAES', $com->area_destino_nombre);
    }
}
