<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Communication;
use App\Models\User;
use App\Services\CommunicationService;
use App\Services\UserService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
        $service = app(CommunicationService::class);
        $users = $this->ensureDemoUsers();

        $summary = ['ci' => 0, 'of' => 0, 'anuladas' => 0];
        $byArea = [];
        $start = Carbon::create(2026, 1, 1, 0, 0, 0);
        $spanMinutes = $start->diffInMinutes(now());

        for ($i = 0; $i < self::COUNT; $i++) {
            $user = $users->random();
            $type = fake()->boolean(70) ? Communication::TYPE_INTERNAL : Communication::TYPE_EXTERNAL;

            $communication = $service->create($user, $this->payload($type, $users));

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

            $areaCode = $communication->area?->code ?? 'sin-area';
            $byArea[$areaCode] = ($byArea[$areaCode] ?? 0) + 1;

            if (fake()->boolean(12)) {
                $service->annul($communication);
                $summary['anuladas']++;
            }

            $summary[$type]++;
        }

        $areas = collect($byArea)->sort()->map(fn ($count, $code) => "{$code}: {$count}")->implode(', ');

        $this->command?->info(
            'Se generaron '.self::COUNT." comunicaciones: {$summary['ci']} ci, {$summary['of']} of, {$summary['anuladas']} anuladas."
        );
        $this->command?->info("Por área: {$areas}");
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<string, mixed>
     */
    private function payload(string $type, Collection $users): array
    {
        $internal = fake()->boolean(60) ? $users->random() : null;
        $destino = $type === Communication::TYPE_INTERNAL || fake()->boolean()
            ? Area::inRandomOrder()->first()
            : null;

        $destinoData = [
            'area_destino_id' => $destino?->id,
            'area_destino_nombre' => $destino?->name,
        ];

        if ($internal !== null) {
            $position = $internal->currentAssignment()->first()?->position;

            return [
                'type' => $type,
                'reference' => fake()->sentence(),
                'recipient_name' => $internal->name,
                'recipient_position' => $position?->name,
                'recipient_user_id' => $internal->id,
                ...$destinoData,
            ];
        }

        return [
            'type' => $type,
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
        $userService = app(UserService::class);

        foreach (Area::all() as $area) {
            foreach ($area->positions as $position) {
                $user = User::firstOrCreate(
                    ['email' => UserService::emailFor("demo-{$area->code}-{$position->code}")],
                    [
                        'name' => "Demo {$area->name} - {$position->name}",
                        'password' => Hash::make('password'),
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
