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
 * Genera 50 comunicaciones internas (ci) y oficios externos (of) aleatorios
 * en todas las áreas, siguiendo el flujo real de numeración.
 *
 * Ejecutar con: php artisan db:seed --class=RandomCommunicationsSeeder
 *
 * NOTA: las fechas arrancan fijas el 01/01/2026 y avanzan +8h por registro,
 * así que solo cubren ~16 días (enero). Para datos repartidos hasta hoy,
 * usar DemoCommunicationsSeeder.
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
        // Pool de creadores/destinatarios: un usuario demo por área y puesto.
        $users = $this->ensureDemoUsers();

        $summary = ['ci' => 0, 'of' => 0, 'anuladas' => 0];
        $byArea = [];

        // Fecha base fija: cada registro suma +8h, cubriendo ~16 días.
        $createdAt = Carbon::create(2026, 1, 1, 0, 0, 0);

        // Precarga de áreas con su área de numeración (un solo nivel).
        $areas = Area::with('positions')->get()->keyBy('id');
        $numberingAreas = $this->resolveNumberingAreas($areas);

        // Secuencias en memoria por área de numeración y tipo, continuando
        // desde el contador persistido para no pisar numeración existente.
        $sequences = [];
        $year = 2026;

        for ($i = 0; $i < self::COUNT; $i++) {
            // Creador al azar y tipo de documento (70% internas).
            $user = $users->random();
            $type = fake()->boolean(70) ? Communication::TYPE_INTERNAL : Communication::TYPE_EXTERNAL;

            // Área real del creador y su área de numeración.
            $user->loadMissing('currentAssignment.position.area');
            $area = $user->currentArea;
            $numberingArea = $numberingAreas[$area->id] ?? $area;

            // Siguiente número del correlativo: prefijo.tipo.codigo.secuencia/año.
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

            // Antedata created_at (updated_at queda en "ahora") y avanza +8h.
            $communication->update([
                'created_at' => $createdAt->copy(),
            ]);
            $createdAt->addHours(8);

            $areaCodeReal = $communication->area?->code ?? 'sin-area';
            $byArea[$areaCodeReal] = ($byArea[$areaCodeReal] ?? 0) + 1;

            // 12% de anuladas (conservan su número, nunca se reutiliza).
            if (fake()->boolean(12)) {
                $communication->update(['status' => Communication::STATUS_ANNULLED]);
                $summary['anuladas']++;
            }

            $summary[$type]++;
        }

        // Sincroniza los contadores persistidos con lo generado.
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
     * Arma los datos del destinatario: interno (usuario del pool, 60%) o
     * externo libre con nombre inventado.
     *
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
     * Garantiza un usuario demo por cada área y puesto (demo-{area}-{puesto}).
     *
     * Crea los faltantes con firstOrCreate (contraseña 'password', rol
     * usuario, avatar de galería) y les asigna su puesto; los ya existentes
     * se reutilizan. Antes elimina los demo antiguos de formato "uno por
     * área" (demo-{area}). Devuelve la colección que el run() usa como
     * creadores y destinatarios de las comunicaciones.
     *
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
     * Elimina los usuarios demo legacy de formato "uno por área"
     * (demo-{codigo}@example.com). Sus asignaciones y comunicaciones
     * se borran en cascada.
     */
    private function removeLegacyDemoUsers(Area $area): void
    {
        User::where('email', UserService::emailFor("demo-{$area->code}"))->delete();
    }
}
