<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Communication;
use App\Models\AreaNumberCounter;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Generate random internal (ci) and external (of) communications across all
 * areas, following the real numbering flow through CommunicationService.
 *
 * Run with: php artisan db:seed --class=RandomCommunicationsSeeder
 */
class RandomCommunicationsSeeder extends Seeder
{
    private const COUNT = 50;

    /**
     * @var array<int, string>
     */
    private const FIRST_NAMES = [
        'Juan', 'María', 'Carlos', 'Ana', 'Luis', 'Carmen', 'José', 'Sofía',
        'Pedro', 'Lucía', 'Miguel', 'Valentina', 'Andrés', 'Gabriela', 'Raúl', 'Fernanda',
    ];

    /**
     * @var array<int, string>
     */
    private const LAST_NAMES = [
        'Mamani', 'Flores', 'García', 'Quispe', 'Rodríguez', 'López', 'Choque', 'Ramírez',
        'Mendoza', 'Vargas', 'Pérez', 'Gutiérrez', 'Salazar', 'Molina', 'Rojas', 'Torres',
    ];

    public function run(): void
    {
        $users = $this->ensureDemoUsers();

        $summary = ['ci' => 0, 'of' => 0, 'anuladas' => 0];
        $byArea = [];
        $createdAt = Carbon::create(2026, 1, 1, 0, 0, 0);

        // Pre-load all areas and their numbering areas
        $areas = Area::with('positions')->get()->keyBy('id');
        $numberingAreas = $this->resolveNumberingAreas($areas);
        
        // Pre-calculate sequences for each numbering_area/type/year
        $sequences = [];
        $year = 2026;

        for ($i = 0; $i < self::COUNT; $i++) {
            $user = $users->random();
            $type = fake()->boolean(70) ? Communication::TYPE_INTERNAL : Communication::TYPE_EXTERNAL;

            $user->loadMissing('currentAssignment.position.area');
            $area = $user->currentArea;
            $numberingArea = $numberingAreas[$area->id] ?? $area;
            
            $key = $numberingArea->id . '_' . $type . '_' . $year;
            if (!isset($sequences[$key])) {
                $sequences[$key] = (int) AreaNumberCounter::where('area_id', $numberingArea->id)
                    ->where('type', $type)
                    ->where('year', $year)
                    ->value('last_sequence') ?? 0;
            }
            $sequences[$key]++;
            $sequence = $sequences[$key];
            
            $areaCode = strtolower($numberingArea->code);
            $number = sprintf('%s.%s.%04d/%d', $type, $areaCode, $sequence, $year);
            
            $payload = $this->payload($type, $users, $user, $numberingArea);
            $payload['type'] = $type;
            $payload['number'] = $number;
            $payload['year'] = $year;
            $payload['sequence'] = $sequence;
            $payload['area_id'] = $area->id;
            $payload['user_id'] = $user->id;
            $payload['position_id'] = $user->currentAssignment?->position_id;
            $payload['status'] = Communication::STATUS_ACTIVE;

            $communication = Communication::create($payload);
            
            $communication->update([
                'created_at' => $createdAt->copy(),
            ]);
            $createdAt->addHours(8);

            $areaCodeReal = $communication->area?->code ?? 'sin-area';
            $byArea[$areaCodeReal] = ($byArea[$areaCodeReal] ?? 0) + 1;

            if (fake()->boolean(12)) {
                $communication->update(['status' => Communication::STATUS_ANNULLED]);
                $summary['anuladas']++;
            }

            $summary[$type]++;
        }

        // Update counters in bulk
        foreach ($sequences as $key => $sequence) {
            [$areaId, $type, $yearStr] = explode('_', $key);
            AreaNumberCounter::updateOrCreate(
                ['area_id' => $areaId, 'type' => $type, 'year' => (int)$yearStr],
                ['last_sequence' => $sequence],
            );
        }

        $areasSummary = collect($byArea)->sort()->map(fn ($count, $code) => "{$code}: {$count}")->implode(', ');

        $this->command?->info(
            "Se generaron ".self::COUNT." comunicaciones: {$summary['ci']} ci, {$summary['of']} of, {$summary['anuladas']} anuladas."
        );
        $this->command?->info("Por área: {$areasSummary}");
    }

    private function resolveNumberingAreas(Collection $areas): array
    {
        $result = [];
        foreach ($areas as $area) {
            $numberingArea = $area->numbering_area_id !== null
                ? ($areas->get($area->numbering_area_id) ?? $area)
                : $area;
            $result[$area->id] = $numberingArea;
        }
        return $result;
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<string, mixed>
     */
    private function payload(string $type, Collection $users, User $user, Area $numberingArea): array
    {
        $internal = fake()->boolean(60) ? $users->random() : null;

        if ($internal !== null) {
            $position = $internal->currentAssignment?->position;

            return [
                'reference' => fake()->sentence(),
                'recipient_name' => $internal->name,
                'recipient_position' => $position?->name,
                'recipient_user_id' => $internal->id,
            ];
        }

        return [
            'reference' => fake()->sentence(),
            'recipient_name' => fake()->name(),
            'recipient_position' => fake()->jobTitle(),
            'recipient_user_id' => null,
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private function ensureDemoUsers(): Collection
    {
        $users = collect();
        $userService = app(UserService::class);

        $allAreas = Area::with('positions')->get();
        $passwordHash = Hash::make('password'); // Hash once

        foreach ($allAreas as $area) {
            $this->removeLegacyDemoUsers($area);

            foreach ($area->positions as $position) {
                $user = User::firstOrCreate(
                    ['email' => UserService::emailFor("demo-{$area->code}-{$position->code}")],
                    [
                        'name' => "Demo {$area->name} - {$position->name}",
                        'password' => $passwordHash,
                        'is_active' => true,
                        'avatar_kind' => 'gallery',
                        'avatar_value' => 'ocean',
                    ],
                );

                $user->assignRole('usuario');
                $userService->assignPosition($user, $position->id);

                $users->push($user);
            }
        }

        return $users;
    }

    /**
     * Remove legacy "one user per area" demo users (demo-{code}@example.com).
     * Their position assignments and communications cascade-delete.
     */
    private function removeLegacyDemoUsers(Area $area): void
    {
        User::where('email', UserService::emailFor("demo-{$area->code}"))->delete();
    }
}
