<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Communication;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Services\CommunicationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\HasCommunicationTypes;
use Tests\TestCase;

class CommunicationReportTest extends TestCase
{
    use HasCommunicationTypes, RefreshDatabase;

    private function makeArea(string $code, string $name): Area
    {
        return Area::create([
            'code' => $code,
            'name' => $name,
            'parent_id' => null,
            'is_active' => true,
        ]);
    }

    private function makeUser(string $name, string $username, string $role, Area $area): User
    {
        $position = Position::create([
            'area_id' => $area->id,
            'code' => 'J-'.$username,
            'name' => 'JEFE',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = User::create([
            'name' => $name,
            'ci' => fake()->unique()->numerify('#######'),
            'email' => $username.'@carto.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole($role);

        PositionAssignment::create([
            'user_id' => $user->id,
            'position_id' => $position->id,
            'started_at' => now(),
            'ended_at' => null,
        ]);

        return $user;
    }

    private function makeComm(User $author, string $number = 'ci.carto.0001/2026'): void
    {
        $service = app(CommunicationService::class);

        $service->create($author->fresh(), [
            'communication_type_id' => $this->communicationType()->id,
            'reference' => 'Ref '.$number,
            'recipient_name' => 'Dest',
            'recipient_position' => 'TECNICO',
            'area_destino_nombre' => 'CARTO',
        ]);
    }

    public function test_report_streams_pdf_scoped_to_user_area(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $carto = $this->makeArea('CARTO', 'DEPARTAMENTO DE CARTOGRAFIA');
        $other = $this->makeArea('OTHER', 'OTHER AREA');

        $user = $this->makeUser('Usuario', 'usuario', 'usuario', $carto);
        $outsider = $this->makeUser('Externo', 'externo', 'usuario', $other);

        $this->makeComm($user);
        $this->makeComm($user);
        $this->makeComm($outsider);

        $from = now()->subDay()->toDateString();
        $to = now()->addDay()->toDateString();

        $response = $this->actingAs($user)->get(
            "/dashboard/reporte?from={$from}&to={$to}"
        );

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);

        $rows = app(CommunicationService::class)->getReportData($user->fresh(), $from, $to, false);
        $this->assertCount(2, $rows);
    }

    public function test_report_requires_valid_dates(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $carto = $this->makeArea('CARTO', 'DEPARTAMENTO DE CARTOGRAFIA');
        $user = $this->makeUser('Usuario', 'usuario', 'usuario', $carto);

        $this->actingAs($user)->get('/dashboard/reporte')
            ->assertSessionHasErrors(['from', 'to']);
    }
}
