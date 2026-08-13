<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Communication;
use App\Models\User;
use App\Services\CommunicationService;
use App\Services\UserService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
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
        $service = app(CommunicationService::class);
        $users = $this->ensureDemoUsers();

        $summary = ['ci' => 0, 'of' => 0, 'anuladas' => 0];
        $byArea = [];

        for ($i = 0; $i < self::COUNT; $i++) {
            $user = $users->random();
            $type = fake()->boolean(70) ? Communication::TYPE_INTERNAL : Communication::TYPE_EXTERNAL;

            $communication = $service->create($user, $this->payload($type, $users));

            $communication->update([
                'created_at' => fake()->dateTimeBetween(now()->startOfYear(), now()),
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
            "Se generaron ".self::COUNT." comunicaciones: {$summary['ci']} ci, {$summary['of']} of, {$summary['anuladas']} anuladas."
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

        if ($internal !== null) {
            $position = $internal->currentAssignment()->first()?->position;

            return [
                'type' => $type,
                'reference' => fake()->sentence(),
                'recipient_name' => $internal->name,
                'recipient_position' => $position?->name,
                'recipient_user_id' => $internal->id,
            ];
        }

        return [
            'type' => $type,
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

        foreach (Area::all() as $area) {
            $this->removeLegacyDemoUsers($area);

            foreach ($area->positions as $position) {
                $user = User::firstOrCreate(
                    ['email' => "demo-{$area->code}-{$position->code}@example.com"],
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

    /**
     * Remove legacy "one user per area" demo users (demo-{code}@example.com).
     * Their position assignments and communications cascade-delete.
     */
    private function removeLegacyDemoUsers(Area $area): void
    {
        User::where('email', "demo-{$area->code}@example.com")->delete();
    }
}
