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

class DashboardScopeTest extends TestCase
{
    use HasCommunicationTypes, RefreshDatabase;

    private function makeUser(string $name, string $username, string $role, Position $position): User
    {
        $user = User::create([
            'name' => $name,
            'ci' => fake()->unique()->numerify('#######'),
            'email' => \App\Services\UserService::emailFor($username),
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

    public function test_basic_user_sees_area_rows_in_user_stats(): void
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
            'code' => 'TECNICO',
            'name' => 'TECNICO',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $userA = $this->makeUser('Usuario A', 'usera', 'usuario', $position);
        $userB = $this->makeUser('Usuario B', 'userb', 'usuario', $position);

        $service = app(CommunicationService::class);
        $payload = [
            'communication_type_id' => $this->communicationType()->id,
            'reference' => 'Ref',
            'recipient_name' => 'Dest',
            'recipient_position' => 'TECNICO',
            'area_destino_nombre' => 'CARTO',
        ];

        $service->create($userA, $payload);
        $service->create($userA, $payload);
        $service->create($userB, $payload);

        $from = now()->startOfYear()->toDateString();
        $to = now()->endOfYear()->toDateString();

        $statsA = $service->getUserStats($userA->fresh(), $from, $to);
        $this->assertCount(2, $statsA);
        $this->assertSame('usera', $statsA[0]['destino']);
        $this->assertSame(2, $statsA[0]['ci']);
        $this->assertSame('userb', $statsA[1]['destino']);
        $this->assertSame(1, $statsA[1]['ci']);

        $admin = $this->makeUser('Admin', 'adminx', 'administrador', $position);
        $statsAdmin = $service->getUserStats($admin->fresh(), $from, $to);
        $this->assertCount(2, $statsAdmin);
        $this->assertSame(3, array_sum(array_column($statsAdmin, 'ci')));
    }

    public function test_monthly_stats_are_scoped_to_user_area_for_any_role(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $carto = Area::create([
            'code' => 'CARTO',
            'name' => 'DEPARTAMENTO DE CARTOGRAFIA',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $other = Area::create([
            'code' => 'OTRO',
            'name' => 'OTRO',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $cartoPosition = Position::create([
            'area_id' => $carto->id,
            'code' => 'TECNICO',
            'name' => 'TECNICO',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $otherPosition = Position::create([
            'area_id' => $other->id,
            'code' => 'TECNICO',
            'name' => 'TECNICO',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $admin = $this->makeUser('Admin', 'adminx', 'administrador', $cartoPosition);
        $outsider = $this->makeUser('Externo', 'externo', 'usuario', $otherPosition);

        $service = app(CommunicationService::class);
        $payload = [
            'communication_type_id' => $this->communicationType()->id,
            'reference' => 'Ref',
            'recipient_name' => 'Dest',
            'recipient_position' => 'TECNICO',
            'area_destino_nombre' => 'CARTO',
        ];

        $service->create($admin, $payload);
        $service->create($admin, $payload);
        $service->create($admin, $payload);
        $service->create($outsider, $payload);

        $monthly = $service->getDashboardStats($admin->fresh(), now()->year);
        $this->assertSame(3, array_sum(array_column($monthly, 'total')));
    }

    public function test_monthly_stats_include_area_subtrees(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $carto = Area::create([
            'code' => 'carto',
            'name' => 'DEPARTAMENTO DE CARTOGRAFIA',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $sit = Area::create([
            'code' => 'sit',
            'name' => 'SISTEMAS DE INFORMACION TERRITORIAL',
            'parent_id' => $carto->id,
            'is_active' => true,
        ]);

        $unrelated = Area::create([
            'code' => 'taes',
            'name' => 'DEPARTAMENTO DE TAES',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $cartoPosition = Position::create([
            'area_id' => $carto->id,
            'code' => 'TECNICO',
            'name' => 'TECNICO',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $sitPosition = Position::create([
            'area_id' => $sit->id,
            'code' => 'TECNICO',
            'name' => 'TECNICO',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $unrelatedPosition = Position::create([
            'area_id' => $unrelated->id,
            'code' => 'TECNICO',
            'name' => 'TECNICO',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $parentUser = $this->makeUser('Padre', 'padre', 'administrador', $cartoPosition);
        $childUser = $this->makeUser('Hijo', 'hijo', 'usuario', $sitPosition);
        $outsiderUser = $this->makeUser('Ajeno', 'ajeno', 'usuario', $unrelatedPosition);

        $service = app(CommunicationService::class);
        $payload = [
            'communication_type_id' => $this->communicationType()->id,
            'reference' => 'Ref',
            'recipient_name' => 'Dest',
            'recipient_position' => 'TECNICO',
            'area_destino_nombre' => 'CARTO',
        ];

        $service->create($parentUser, $payload);
        $service->create($childUser, $payload);
        $service->create($childUser, $payload);
        $service->create($outsiderUser, $payload);

        $from = now()->startOfYear()->toDateString();
        $to = now()->endOfYear()->toDateString();

        $parentStats = $service->getDashboardStats($parentUser->fresh(), now()->year);
        $this->assertSame(3, array_sum(array_column($parentStats, 'total')));

        $childStats = $service->getDashboardStats($childUser->fresh(), now()->year);
        $this->assertSame(2, array_sum(array_column($childStats, 'total')));

        $filtered = $service->getDashboardStats($parentUser->fresh(), now()->year, $sit->id);
        $this->assertSame(2, array_sum(array_column($filtered, 'total')));

        $all = $service->getDashboardStats($parentUser->fresh(), now()->year, 'all');
        $this->assertSame(4, array_sum(array_column($all, 'total')));

        $parentUsers = $service->getUserStats($parentUser->fresh(), $from, $to);
        $this->assertSame(3, array_sum(array_column($parentUsers, 'total')));

        $report = $service->getReportData($parentUser->fresh(), $from, $to);
        $this->assertCount(3, $report);
    }
}
