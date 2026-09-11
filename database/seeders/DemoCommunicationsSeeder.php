<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Communication;
use App\Models\AreaNumberCounter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Genera 500 comunicaciones internas (ci) y oficios externos (of) de ejemplo
 * con fechas repartidas del 01/01/2026 hasta hoy.
 *
 * Usa los usuarios ya existentes en la base de datos (TestUsersSeeder se
 * ejecuta antes) y no crea usuarios nuevos. Corre al final de DatabaseSeeder.
 *
 * NOTA: inserta con bulk insert, que omite los eventos de Eloquent. Hoy
 * Communication no tiene observers; si se agrega uno (ej. auditoría),
 * este seeder lo pasaría por alto.
 */
class DemoCommunicationsSeeder extends Seeder
{
    private const COUNT = 500;

    private const CHUNK_SIZE = 250;

    public function run(): void
    {
        // Pool de creadores/destinatarios: solo usuarios activos con puesto
        // vigente, porque el área de la comunicación deriva del puesto actual.
        $users = User::where('is_active', true)
            ->with('currentAssignment.position.area')
            ->get()
            ->filter(fn (User $user) => $user->currentArea !== null)
            ->values();

        if ($users->isEmpty()) {
            throw new \RuntimeException(
                'DemoCommunicationsSeeder necesita usuarios activos con puesto vigente. Ejecuta TestUsersSeeder primero.'
            );
        }

        // Reloj fijo para toda la corrida: evita cientos de llamadas a now()
        // y deja fechas coherentes entre filas y contadores.
        $now = now();
        $start = Carbon::create(2026, 1, 1, 0, 0, 0);
        $spanMinutes = (int) $start->diffInMinutes($now);

        // Mapa de áreas con su área de numeración (un solo nivel): el número
        // usa el código del área apuntada, pero area_id guarda el área real.
        $areas = Area::with('positions')->get()->keyBy('id');
        $numberingAreas = $this->resolveNumberingAreas($areas);

        // Secuencias en memoria por área de numeración y tipo, continuando
        // desde el contador persistido para no pisar numeración existente.
        $sequences = [];
        $year = 2026;

        $summary = ['ci' => 0, 'of' => 0, 'anuladas' => 0];
        $byArea = [];
        $rows = [];

        for ($i = 0; $i < self::COUNT; $i++) {
            // Creador al azar y tipo de documento (70% internas).
            $user = $users->random();
            $type = fake()->boolean(70) ? Communication::TYPE_INTERNAL : Communication::TYPE_EXTERNAL;

            // Área real del creador y su área de numeración.
            $area = $user->currentArea;
            $numberingArea = $numberingAreas[$area->id] ?? $area;

            // Siguiente número del correlativo: prefijo.tipo.codigo.secuencia/año.
            $sequences[$numberingArea->id][$type] ??= (int) AreaNumberCounter::where('area_id', $numberingArea->id)
                ->where('type', $type)
                ->where('year', $year)
                ->value('last_sequence') ?? 0;
            $sequence = ++$sequences[$numberingArea->id][$type];
            $number = sprintf('%s.%s.%04d/%d', $type, strtolower($numberingArea->code), $sequence, $year);

            // Fecha simulada: reparte los registros proporcionalmente entre
            // el 01/01 y hoy, con jitter de 0-30 min y tope en el presente.
            $stampedAt = $start->copy()->addMinutes(
                (int) ($i * $spanMinutes / self::COUNT) + fake()->numberBetween(0, 30)
            );
            if ($stampedAt->isAfter($now)) {
                $stampedAt = $now->copy();
            }

            // Estado final sorteado de una vez (12% anuladas) para no hacer
            // un UPDATE posterior por registro.
            $annulled = fake()->boolean(12);

            // Fila lista para bulk insert, con UUID y marcas de tiempo propias.
            $rows[] = [
                'id' => (string) Str::orderedUuid(),
                ...$this->payload($type, $users, $user),
                'type' => $type,
                'number' => $number,
                'year' => $year,
                'sequence' => $sequence,
                'area_id' => $area->id,
                'user_id' => $user->id,
                'position_id' => $user->currentAssignment?->position_id,
                'status' => $annulled ? Communication::STATUS_ANNULLED : Communication::STATUS_ACTIVE,
                'created_at' => $stampedAt,
                'updated_at' => $stampedAt,
            ];

            // Conteo por área real usando el mapa precargado (sin queries N+1).
            $areaCodeReal = $areas->get($area->id)?->code ?? 'sin-area';
            $byArea[$areaCodeReal] = ($byArea[$areaCodeReal] ?? 0) + 1;

            if ($annulled) {
                $summary['anuladas']++;
            }
            $summary[$type]++;
        }

        // Escritura atómica en chunks: 2 inserts en vez de ~1000 queries.
        DB::transaction(function () use ($rows, $sequences, $year) {
            foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                Communication::insert($chunk);
            }

            // Sincroniza los contadores persistidos con lo generado.
            foreach ($sequences as $areaId => $byType) {
                foreach ($byType as $type => $sequence) {
                    AreaNumberCounter::updateOrCreate(
                        ['area_id' => $areaId, 'type' => $type, 'year' => $year],
                        ['last_sequence' => $sequence],
                    );
                }
            }
        });

        $areasSummary = collect($byArea)->sort()->map(fn ($count, $code) => "{$code}: {$count}")->implode(', ');

        $this->command?->info(
            'Se generaron '.self::COUNT." comunicaciones: {$summary['ci']} ci, {$summary['of']} of, {$summary['anuladas']} anuladas."
        );
        $this->command?->info("Por área: {$areasSummary}");
    }

    /**
     * Resuelve el área de numeración de cada área (un solo nivel).
     *
     * @return array<string, Area>
     */
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
     * externo libre, más el área destino opcional.
     *
     * @param  Collection<int, User>  $users
     * @return array<string, mixed>
     */
    private function payload(string $type, Collection $users): array
    {
        // Destinatario interno sorteado del pool o destinatario externo libre.
        $internal = fake()->boolean(60) ? $users->random() : null;

        // Área destino: siempre posible en ci; en of, mitad de las veces.
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
}
