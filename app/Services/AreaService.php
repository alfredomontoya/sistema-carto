<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Communication;
use App\Repositories\Contracts\AreaRepository;
use App\Repositories\Contracts\PositionRepository;
use Illuminate\Support\Facades\DB;

class AreaService
{
    public function __construct(
        private readonly AreaRepository $areas,
        private readonly PositionRepository $positions,
        private readonly NumberSequenceService $numbers,
    ) {}

    /**
     * Puestos por defecto ofrecidos al crear un área.
     * Fuente editable: config/positions.php.
     *
     * @return array<int, array{name: string, code: string, sort_order: int}>
     */
    public static function defaultPositions(): array
    {
        return config('positions.defaults', []);
    }

    /**
     * @return array<int, string>
     */
    public static function defaultPositionCodes(): array
    {
        return array_column(self::defaultPositions(), 'code');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tree(): array
    {
        return $this->areas->tree();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Area>
     */
    public function all()
    {
        return $this->areas->all();
    }

    public function find(string $id): ?Area
    {
        return $this->areas->find($id);
    }

    /**
     * Default area used when a communication has no registered
     * destination area. Created on demand if missing.
     */
    public function defaultArea(): Area
    {
        return $this->areas->findByCode('otro')
            ?? $this->areas->create([
                'code' => 'otro',
                'name' => 'OTRO',
                'parent_id' => null,
                'is_active' => true,
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Area
    {
        $positionCodes = $data['positions'] ?? self::defaultPositionCodes();
        unset($data['positions']);

        return DB::transaction(function () use ($data, $positionCodes): Area {
            $area = $this->areas->create($data);
            $this->createPositions($area->id, (array) $positionCodes);

            return $area;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Area $area, array $data): Area
    {
        $positionCodes = array_key_exists('positions', $data) ? $data['positions'] : null;
        unset($data['positions']);

        if (($data['numbering_area_id'] ?? null) === $area->id) {
            $data['numbering_area_id'] = null;
        }

        return DB::transaction(function () use ($area, $data, $positionCodes): Area {
            $updated = $this->areas->update($area, $data);

            if (is_array($positionCodes)) {
                $this->syncPositions($updated->id, $positionCodes);
            }

            return $updated;
        });
    }

    /**
     * Crea los puestos seleccionados de la lista por defecto.
     *
     * @param  array<int, string>  $codes
     */
    private function createPositions(string $areaId, array $codes): void
    {
        $defaults = collect(self::defaultPositions())->keyBy('code');

        foreach (array_values(array_unique($codes)) as $code) {
            $default = $defaults->get($code);

            if ($default === null) {
                continue;
            }

            $this->positions->create([
                'area_id' => $areaId,
                'name' => $default['name'],
                'code' => $default['code'],
                'is_active' => true,
                'sort_order' => $default['sort_order'] ?? 0,
            ]);
        }
    }

    /**
     * Sincroniza los puestos del área con la selección del diálogo:
     * el code seleccionado e inexistente se crea activo, el existente
     * se activa o desactiva. Nunca se elimina (preserva el historial).
     * Las entradas `id:<uuid>` identifican puestos sin código.
     * La comparación es insensible a mayúsculas y el code existente se
     * normaliza al canónico en minúsculas; si solo coincide el nombre
     * (códigos heredados como ABOG), también se adopta el code canónico.
     *
     * @param  array<int, string>  $selected
     */
    private function syncPositions(string $areaId, array $selected): void
    {
        $selected = array_values(array_unique($selected));
        $defaults = collect(self::defaultPositions())->keyBy('code');
        $existing = $this->positions->forArea($areaId);

        $byId = [];
        $byCode = [];

        foreach ($existing as $position) {
            $byId[$position->id] = $position;

            if ($position->code !== null) {
                $byCode[mb_strtolower($position->code)] ??= $position;
            }
        }

        $findExisting = function (string $code) use ($byCode, $defaults, $existing) {
            if (isset($byCode[$code])) {
                return $byCode[$code];
            }

            $default = $defaults->get($code);

            if ($default === null) {
                return null;
            }

            $name = mb_strtolower($default['name']);

            foreach ($existing as $position) {
                if (mb_strtolower($position->name) === $name) {
                    return $position;
                }
            }

            return null;
        };

        $activeIds = [];

        foreach ($selected as $entry) {
            if (str_starts_with($entry, 'id:')) {
                $id = substr($entry, 3);

                if (isset($byId[$id])) {
                    $activeIds[$id] = true;
                }

                continue;
            }

            $found = $findExisting($entry);

            if ($found !== null) {
                $needsCode = $found->code === null || mb_strtolower($found->code) !== $entry;
                $collision = isset($byCode[$entry]) && $byCode[$entry]->id !== $found->id;

                if ($needsCode && ! $collision) {
                    $this->positions->update($found, ['code' => $entry]);
                }

                $activeIds[$found->id] = true;

                continue;
            }

            $default = $defaults->get($entry);

            if ($default === null) {
                continue;
            }

            $created = $this->positions->create([
                'area_id' => $areaId,
                'name' => $default['name'],
                'code' => $default['code'],
                'is_active' => true,
                'sort_order' => $default['sort_order'] ?? 0,
            ]);

            $activeIds[$created->id] = true;
        }

        foreach ($existing as $position) {
            $active = isset($activeIds[$position->id]);

            if ($position->is_active !== $active) {
                $this->positions->update($position, ['is_active' => $active]);
            }
        }
    }

    /**
     * Deletes an area. Refuses if it has children, positions or
     * communications (to never lose correlative numbers).
     *
     * @return bool true when deleted, false when blocked
     */
    public function delete(Area $area): bool
    {
        if ($this->areas->hasChildren($area)) {
            return false;
        }

        if ($area->positions()->exists()) {
            return false;
        }

        if ($area->communications()->exists()) {
            return false;
        }

        $this->areas->delete($area);

        return true;
    }

    /**
     * Guard: an area cannot be moved under one of its own descendants.
     */
    public function wouldCreateCycle(Area $area, ?string $newParentId): bool
    {
        if ($newParentId === null) {
            return false;
        }

        if ($newParentId === $area->id) {
            return true;
        }

        return in_array($newParentId, $this->areas->descendantIds($area), true);
    }

    /**
     * @return array<int, array{area_id: string, area_name: string, area_code: string, types: array<int, array{type_id: string, code: string, name: string, current: int, issued_max: int}>}>
     */
    public function numberingOverview(int $year, ?string $search = null): array
    {
        return $this->numbers->overview($year, $search);
    }

    /**
     * Reset the numbering of an area back to zero for the current year.
     * When no type ids are given, every communication type is reset.
     * Refuses when numbers were already issued in the selected scope
     * (they could duplicate correlatives), unless forced.
     *
     * @param  array<int, string>  $communicationTypeIds
     * @return bool true when reset, false when blocked
     */
    public function resetNumbering(Area $area, array $communicationTypeIds = [], bool $force = false): bool
    {
        $year = now()->year;

        return DB::transaction(function () use ($area, $communicationTypeIds, $year, $force): bool {
            $issuedQuery = Communication::where('area_id', $area->id)
                ->where('year', $year);

            if ($communicationTypeIds !== []) {
                $issuedQuery->whereIn('communication_type_id', $communicationTypeIds);
            }

            $issuedMax = (int) $issuedQuery->lockForUpdate()->max('sequence');

            if ($issuedMax > 0 && ! $force) {
                return false;
            }

            $this->numbers->resetForYear($area, $year, $communicationTypeIds);

            return true;
        });
    }

    /**
     * Set the numbering of an area/type to an explicit value for the current
     * year. Refuses when the value is at or below the highest sequence
     * already issued (it would duplicate correlatives), unless forced.
     *
     * @return bool true when set, false when blocked
     */
    public function setNumbering(Area $area, string $communicationTypeId, int $value, bool $force = false): bool
    {
        $year = now()->year;

        return DB::transaction(function () use ($area, $communicationTypeId, $value, $year, $force): bool {
            $issuedMax = (int) Communication::where('area_id', $area->id)
                ->where('year', $year)
                ->where('communication_type_id', $communicationTypeId)
                ->lockForUpdate()
                ->max('sequence');

            if ($issuedMax > 0 && $value <= $issuedMax && ! $force) {
                return false;
            }

            $this->numbers->setSequence($area, $communicationTypeId, $year, $value);

            return true;
        });
    }
}
