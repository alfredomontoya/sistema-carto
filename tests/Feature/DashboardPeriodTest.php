<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Communication;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Repositories\Eloquent\EloquentCommunicationRepository;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardPeriodTest extends TestCase
{
    use RefreshDatabase;

    private EloquentCommunicationRepository $repo;

    private Area $carto;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->carto = Area::create([
            'code' => 'carto',
            'name' => 'DEPARTAMENTO DE CARTOGRAFIA',
            'is_active' => true,
        ]);

        $position = Position::create([
            'area_id' => $this->carto->id,
            'code' => 'tecnico',
            'name' => 'TECNICO',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->user = User::create([
            'name' => 'Tester',
            'email' => 'tester@carto.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $this->user->assignRole('usuario');

        PositionAssignment::create([
            'user_id' => $this->user->id,
            'position_id' => $position->id,
            'started_at' => now(),
            'ended_at' => null,
        ]);

        $this->repo = new EloquentCommunicationRepository();
    }

    public function test_today_range_includes_records_created_during_the_day(): void
    {
        $this->makeCommunication(Carbon::today()->setTime(1, 44));
        $today = today()->toDateString();

        $this->assertSame(1, $this->totalOf($this->repo->getDestinoStats($today, $today)));
        $this->assertSame(1, $this->totalOf($this->repo->getUserStats($today, $today)));
    }

    public function test_yesterday_range_includes_the_whole_day(): void
    {
        $yesterday = today()->subDay();
        $this->makeCommunication($yesterday->copy()->setTime(15, 30));

        $range = $yesterday->toDateString();

        $this->assertSame(1, $this->totalOf($this->repo->getDestinoStats($range, $range)));
        $this->assertSame(1, $this->totalOf($this->repo->getUserStats($range, $range)));
    }

    public function test_end_date_is_inclusive_up_to_end_of_day(): void
    {
        $yesterday = today()->subDay()->toDateString();
        $today = today()->toDateString();

        $this->makeCommunication(Carbon::today()->setTime(23, 59, 59));
        $this->makeCommunication(Carbon::tomorrow()->startOfDay());

        $this->assertSame(1, $this->totalOf($this->repo->getDestinoStats($yesterday, $today)));
        $this->assertSame(1, $this->totalOf($this->repo->getUserStats($yesterday, $today)));
    }

    public function test_destino_stats_are_ordered_by_area_code(): void
    {
        $taes = Area::create([
            'code' => 'taes',
            'name' => 'DEPARTAMENTO DE TAES',
            'is_active' => true,
        ]);

        $this->makeCommunication(Carbon::today()->setTime(10, 0), $taes->id);
        $this->makeCommunication(Carbon::today()->setTime(11, 0), $this->carto->id);
        $this->makeCommunication(Carbon::today()->setTime(12, 0), $taes->id);

        $today = today()->toDateString();
        $rows = $this->repo->getDestinoStats($today, $today);

        $this->assertSame(['carto', 'taes'], array_column($rows, 'destino'));
    }

    private function makeCommunication(Carbon $at, ?string $destinoAreaId = null): void
    {
        $communication = Communication::create([
            'type' => Communication::TYPE_INTERNAL,
            'number' => 'ci.carto.0001/2026',
            'year' => 2026,
            'sequence' => 1,
            'area_id' => $this->carto->id,
            'user_id' => $this->user->id,
            'reference' => 'Regresión de períodos',
            'recipient_name' => 'Destinatario',
            'area_destino_id' => $destinoAreaId,
            'status' => Communication::STATUS_ACTIVE,
        ]);

        DB::table('communications')->where('id', $communication->id)->update([
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    /**
     * @param  array<int, array{destino: string, ci: int, of: int, total: int}>  $rows
     */
    private function totalOf(array $rows): int
    {
        return array_sum(array_column($rows, 'total'));
    }
}
