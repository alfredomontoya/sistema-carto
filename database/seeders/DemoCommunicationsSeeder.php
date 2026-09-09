<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Communication;
use App\Models\AreaNumberCounter;
use App\Models\User;
use App\Services\NumberSequenceService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Generate 500 internal (ci) and external (of) communications dated
 * 01/01/2026 through today, following the real numbering flow
 * through CommunicationService. Runs last in DatabaseSeeder.
 */
class DemoCommunicationsSeeder extends Seeder
{
    private const COUNT = 500;

    public function run(): void
    {
        $users = $this->ensureDemoUsers();

        $summary = ['ci' => 0, 'of' => 0, 'anuladas' => 0];
        $byArea = [];
        $start = Carbon::create(2026, 1, 1, 0, 0, 0);
        $spanMinutes = $start->diffInMinutes(now());

        // Pre-load all areas and their numbering areas
        $areas = Area::with('positions')->get()->keyBy('id');
        $numberingAreas = $this->resolveNumberingAreas($areas);
        
        // Pre-calculate sequences for each numbering_area/type/year
        $sequences = [];
        $year = 2026;

        // Build payloads
        $communications = [];
        $annulledIds = [];

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
            
            $stampedAt = $start->copy()->addMinutes(
                (int) ($i * $spanMinutes / self::COUNT) + fake()->numberBetween(0, 30)
            );
            if ($stampedAt->isAfter(now())) {
                $stampedAt = now();
            }
            $communication->update([
                'created_at' => $stampedAt,
                'updated_at' => $stampedAt,
            ]);

            $areaCodeReal = $communication->area?->code ?? 'sin-area';
            $byArea[$areaCodeReal] = ($byArea[$areaCodeReal] ?? 0) + 1;

            if (fake()->boolean(12)) {
                $communication->update(['status' => Communication::STATUS_ANNULLED]);
                $annulledIds[] = $communication->id;
                $summary['anuladas']++;
            }

            $summary[$type]++;
            $communications[] = $communication;
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
            'Se generaron '.self::COUNT." comunicaciones: {$summary['ci']} ci, {$summary['of']} of, {$summary['anuladas']} anuladas."
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
        $destino = $type === Communication::TYPE_INTERNAL || fake()->boolean()
            ? $users->random()?->currentArea
            : null;

        $destinoData = [
            'area_destino_id' => $destino?->id,
            'area_destino_nombre' => $destino?->name,
        ];

        if ($internal !== null) {
            $position = $internal->currentAssignment?->position;

            return [
                'reference' => fake()->sentence(),
                'recipient_name' => $internal->name,
                'recipient_position' => $position?->name,
                'recipient_user_id' => $internal->id,
                ...$destinoData,
            ];
        }

        return [
            'reference' => fake()->sentence(),
            'recipient_name' => fake()->name(),
            'recipient_position' => fake()->jobTitle(),
            'recipient_user_id' => null,
            ...$destinoData,
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private function ensureDemoUsers(): Collection
    {
        $users = collect();
        $userService = app(\App\Services\UserService::class);

        $allAreas = Area::with('positions')->get();
        $passwordHash = Hash::make('password'); // Hash once

        foreach ($allAreas as $area) {
            foreach ($area->positions as $position) {
                $user = User::firstOrCreate(
                    ['email' => \App\Services\UserService::emailFor("demo-{$area->code}-{$position->code}")],
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
}
