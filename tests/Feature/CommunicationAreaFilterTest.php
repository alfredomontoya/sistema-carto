<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Communication;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunicationAreaFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Area $carto;

    private Area $taes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->carto = Area::create([
            'code' => 'carto',
            'name' => 'DEPARTAMENTO DE CARTOGRAFIA',
            'is_active' => true,
        ]);

        $this->taes = Area::create([
            'code' => 'taes',
            'name' => 'DEPARTAMENTO DE TAES',
            'is_active' => true,
        ]);

        $this->admin = $this->makeUser('admin-test@example.com', 'jefe', $this->carto, 'administrador');
        $this->makeUser('demo-taes@example.com', 'tecnico', $this->taes, 'usuario');
    }

    public function test_defaults_to_the_users_area(): void
    {
        $this->createCommunication($this->carto, 'ci', 'ci.carto.0001/2026');
        $this->createCommunication($this->taes, 'ci', 'ci.taes.0001/2026');

        $response = $this->actingAs($this->admin)->get('/comunicaciones');

        $response->assertInertia(fn ($page) => $page
            ->component('Communications/Index')
            ->has('areas', 2)
            ->where('filters.area_id', $this->carto->id)
            ->has('communications', 1)
            ->where('communications.0.area.code', 'carto')
        );
    }

    public function test_all_option_returns_communications_from_every_area(): void
    {
        $this->createCommunication($this->carto, 'ci', 'ci.carto.0001/2026');
        $this->createCommunication($this->taes, 'of', 'of.taes.0001/2026');

        $response = $this->actingAs($this->admin)->get('/comunicaciones?area_id=all');

        $response->assertInertia(fn ($page) => $page
            ->where('filters.area_id', 'all')
            ->has('communications', 2)
        );
    }

    public function test_filters_by_a_specific_area(): void
    {
        $this->createCommunication($this->carto, 'ci', 'ci.carto.0001/2026');
        $this->createCommunication($this->taes, 'ci', 'ci.taes.0001/2026');

        $response = $this->actingAs($this->admin)->get("/comunicaciones?area_id={$this->taes->id}");

        $response->assertInertia(fn ($page) => $page
            ->where('filters.area_id', $this->taes->id)
            ->has('communications', 1)
            ->where('communications.0.area.code', 'taes')
        );
    }

    private function makeUser(string $email, string $positionCode, Area $area, string $role): User
    {
        $position = Position::create([
            'area_id' => $area->id,
            'code' => $positionCode,
            'name' => strtoupper($positionCode),
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = User::create([
            'name' => ucfirst(explode('@', $email)[0]),
            'email' => $email,
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

    private function createCommunication(Area $area, string $type, string $number): void
    {
        Communication::create([
            'type' => $type,
            'number' => $number,
            'year' => now()->year,
            'sequence' => 1,
            'area_id' => $area->id,
            'user_id' => $this->admin->id,
            'reference' => "Comunicación para {$area->code}",
            'recipient_name' => 'Destinatario',
            'status' => Communication::STATUS_ACTIVE,
        ]);
    }
}