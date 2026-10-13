<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Communication;
use App\Models\AreaNumberCounter;
use App\Models\CommunicationType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Genera 500 comunicaciones internas (ci) y oficios externos (of) de ejemplo
 * con fechas repartidas de START_DATE hasta END_DATE (hoy si es null).
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

    private const START_DATE = '2026-10-01';

    private const END_DATE = null;

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

        // Rango fijo para toda la corrida: fechas coherentes entre filas
        // y contadores.
        $start = Carbon::parse(self::START_DATE)->startOfDay();
        $end = self::END_DATE !== null ? Carbon::parse(self::END_DATE)->endOfDay() : now();

        if ($start->isAfter($end)) {
            throw new \RuntimeException(
                'DemoCommunicationsSeeder: START_DATE es posterior a la fecha final.'
            );
        }

        $spanMinutes = (int) $start->diffInMinutes($end);

        // Mapa de áreas con su área de numeración (un solo nivel): el número
        // usa el código del área apuntada, pero area_id guarda el área real.
        $areas = Area::with('positions')->get()->keyBy('id');
        $numberingAreas = $this->resolveNumberingAreas($areas);

        // Communication types for the demo data (derived on demand, not
        // fixed seeds): ci for internal, of for external.
        $ciType = $this->ensureType('ci', 'Comunicación interna', 0);
        $ofType = $this->ensureType('of', 'Oficio externo', 1);

        // Secuencias en memoria por área de numeración, tipo y año (el año
        // deriva de cada fecha generada), continuando desde el contador
        // persistido para no pisar numeración existente.
        $sequences = [];

        $summary = ['ci' => 0, 'of' => 0, 'anuladas' => 0];
        $byArea = [];
        $rows = [];

        for ($i = 0; $i < self::COUNT; $i++) {
            // Creador al azar y tipo de documento (70% internas).
            $user = $users->random();
            $type = fake()->boolean(70) ? $ciType : $ofType;

            // Área real del creador y su área de numeración.
            $area = $user->currentArea;
            $numberingArea = $numberingAreas[$area->id] ?? $area;

            // Fecha simulada: reparte los registros proporcionalmente entre
            // el inicio y el fin del rango, con jitter de 0-30 min y tope
            // en la fecha final.
            $stampedAt = $start->copy()->addMinutes(
                (int) ($i * $spanMinutes / self::COUNT) + fake()->numberBetween(0, 30)
            );
            if ($stampedAt->isAfter($end)) {
                $stampedAt = $end->copy();
            }

            // El año deriva de la fecha generada, no de una constante.
            $year = $stampedAt->year;

            // Siguiente número del correlativo: prefijo.codigo.secuencia/año.
            $sequences[$numberingArea->id][$type->id][$year] ??= (int) AreaNumberCounter::where('area_id', $numberingArea->id)
                ->where('communication_type_id', $type->id)
                ->where('year', $year)
                ->value('last_sequence') ?? 0;
            $sequence = ++$sequences[$numberingArea->id][$type->id][$year];
            $number = sprintf('%s.%s.%s/%d', $type->code, strtolower($numberingArea->code), str_pad((string) $sequence, $type->digits, '0', STR_PAD_LEFT), $year);

            // Estado final sorteado de una vez (12% anuladas) para no hacer
            // un UPDATE posterior por registro.
            $annulled = fake()->boolean(12);

            // Fila lista para bulk insert, con UUID y marcas de tiempo propias.
            $rows[] = [
                'id' => (string) Str::orderedUuid(),
                ...$this->payload($type->code, $users, $user),
                'communication_type_id' => $type->id,
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
            $summary[$type->code]++;
        }

        // Escritura atómica en chunks: 2 inserts en vez de ~1000 queries.
        DB::transaction(function () use ($rows, $sequences) {
            foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                Communication::insert($chunk);
            }

            // Sincroniza los contadores persistidos con lo generado.
            foreach ($sequences as $areaId => $byType) {
                foreach ($byType as $typeId => $byYear) {
                    foreach ($byYear as $year => $sequence) {
                        AreaNumberCounter::updateOrCreate(
                            ['area_id' => $areaId, 'communication_type_id' => $typeId, 'year' => $year],
                            ['last_sequence' => $sequence],
                        );
                    }
                }
            }
        });

        $areasSummary = collect($byArea)->sort()->map(fn ($count, $code) => "{$code}: {$count}")->implode(', ');

        $this->command?->info(
            'Se generaron '.self::COUNT." comunicaciones del {$start->format('d/m/Y')} al {$end->format('d/m/Y')}: {$summary['ci']} ci, {$summary['of']} of, {$summary['anuladas']} anuladas."
        );
        $this->command?->info("Por área: {$areasSummary}");
    }

    /**
     * Get or create a communication type for demo data. Demo seeders derive
     * their own types on demand; no fixed types are seeded by default.
     */
    private function ensureType(string $code, string $name, int $sortOrder): CommunicationType
    {
        return CommunicationType::firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'digits' => 4,
                'reset_annually' => true,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ],
        );
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
        $destino = $type === 'ci' || fake()->boolean()
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
