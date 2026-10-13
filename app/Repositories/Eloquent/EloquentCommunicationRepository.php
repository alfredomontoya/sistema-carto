<?php

namespace App\Repositories\Eloquent;

use App\Models\Area;
use App\Models\Communication;
use App\Models\CommunicationType;
use App\Repositories\Contracts\CommunicationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentCommunicationRepository implements CommunicationRepository
{
    public function find(string $id): ?Communication
    {
        return Communication::with(['communicationType', 'area', 'areaDestino', 'position', 'user.currentAssignment.position.area', 'recipientUser.currentAssignment.position.area'])->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Communication::query()
            ->with(['communicationType', 'area', 'areaDestino', 'position', 'user.currentAssignment.position.area', 'recipientUser.currentAssignment.position.area'])
            ->filter($filters)
            ->orderByDesc('created_at')
            ->orderByDesc('sequence')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Communication
    {
        return Communication::create($data);
    }

    public function update(Communication $communication, array $data): Communication
    {
        $communication->update($data);

        return $communication;
    }

    public function delete(Communication $communication): void
    {
        $communication->delete();
    }

    public function deleteAll(): int
    {
        return Communication::query()->delete();
    }

    public function countForAreaYearType(string $areaId, string $communicationTypeId, int $year): int
    {
        return Communication::where('area_id', $areaId)
            ->where('communication_type_id', $communicationTypeId)
            ->where('year', $year)
            ->count();
    }

    /**
     * Build dynamic per-type count columns for the active communication
     * types, so stats keep working as new types are added.
     *
     * @return array{string, array<int, string>, array<int, string>}
     */
    private function typeCountColumns(string $typeTable, string $countExpr = 'COUNT(*)'): array
    {
        $codes = CommunicationType::ordered()->pluck('code')->all();

        $parts = [];
        $bindings = [];
        foreach ($codes as $code) {
            $parts[] = "SUM(CASE WHEN `{$typeTable}`.`code` = ? THEN 1 ELSE 0 END) as `{$code}`";
            $bindings[] = $code;
        }

        $parts[] = "{$countExpr} as `total`";

        return [implode(', ', $parts), $bindings, $codes];
    }

    /**
     * Normalize a date range to full days so the end date is inclusive
     * up to 23:59:59. Accepts Y-m-d strings or Carbon instances.
     *
     * @return array{Carbon, Carbon}
     */
    private function dayRange(mixed $from, mixed $to): array
    {
        return [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
    }

    /**
     * @param  Builder<Communication>  $query
     */
    private function applyStatsScope(Builder $query, string $table, mixed $from, mixed $to, ?string $areaId, ?string $userId, bool $onlyActive): void
    {
        $prefix = $table === '' ? '' : "{$table}.";

        [$start, $end] = $this->dayRange($from, $to);
        $query->whereBetween("{$prefix}created_at", [$start, $end]);

        if ($onlyActive) {
            $query->where("{$prefix}status", Communication::STATUS_ACTIVE);
        }

        if ($areaId) {
            $query->where("{$prefix}area_id", $areaId);
        }

        if ($userId) {
            $query->where("{$prefix}user_id", $userId);
        }
    }

    /**
     * @return Collection<int, Communication>
     */
    public function reportData(string $from, string $to, ?string $areaId = null, bool $includeAnnulled = false): Collection
    {
        [$start, $end] = $this->dayRange($from, $to);

        $query = Communication::query()
            ->with(['communicationType', 'area', 'areaDestino', 'user'])
            ->whereBetween('created_at', [$start, $end]);

        if (! $includeAnnulled) {
            $query->where('status', Communication::STATUS_ACTIVE);
        }

        if ($areaId) {
            $query->where('area_id', $areaId);
        }

        return $query->orderBy('created_at')
            ->orderBy('sequence')
            ->get();
    }

    /**
     * @param  Collection<int, mixed>  $rows
     * @param  array<int, string>  $codes
     * @return array<int, array<string, int|string>>
     */
    private function mapGroupedStats(Collection $rows, callable $label, array $codes): array
    {
        return $rows->map(function ($row) use ($label, $codes) {
            $counts = [];
            foreach ($codes as $code) {
                $counts[$code] = (int) ($row->{$code} ?? 0);
            }

            return array_merge(
                ['destino' => (string) $label($row)],
                $counts,
                ['total' => (int) $row->total],
            );
        })->values()->all();
    }

    /**
     * @return array<int, array<string, int>>
     */
    public function getMonthlyStats(int $year, ?string $areaId = null, ?string $userId = null): array
    {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 12, 31)->endOfDay();

        $monthExpr = DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', `communications`.`created_at`) AS INTEGER)"
            : 'MONTH(`communications`.`created_at`)';

        [$columns, $bindings, $codes] = $this->typeCountColumns('type_communications');

        $query = Communication::query()
            ->join('type_communications', 'type_communications.id', '=', 'communications.communication_type_id')
            ->selectRaw("{$monthExpr} as `month`, ".$columns, $bindings);

        $this->applyStatsScope($query, 'communications', $start, $end, $areaId, $userId, true);

        return $query->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month')
            ->map(function ($row) use ($codes) {
                $counts = [];
                foreach ($codes as $code) {
                    $counts[$code] = (int) ($row->{$code} ?? 0);
                }

                return array_merge(
                    ['month' => (int) $row->month],
                    $counts,
                    ['total' => (int) $row->total],
                );
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function getAvailableYears(?string $areaId = null, ?string $userId = null): array
    {
        $query = Communication::query()
            ->select('year')
            ->distinct()
            ->where('status', Communication::STATUS_ACTIVE)
            ->orderByDesc('year');

        if ($areaId) $query->where('area_id', $areaId);
        if ($userId) $query->where('user_id', $userId);

        return $query->pluck('year')->all();
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    public function getDestinoStats(string $from, string $to, ?string $areaId = null, ?string $userId = null, bool $includeAnnulled = false, ?array $scopeAreaIds = null): array
    {
        if ($scopeAreaIds !== null) {
            return $this->getDestinoStatsForAreas($from, $to, $scopeAreaIds, $includeAnnulled);
        }

        [$columns, $bindings, $codes] = $this->typeCountColumns('type_communications');

        $query = Communication::query()
            ->leftJoin('areas as destino_areas', 'destino_areas.id', '=', 'communications.area_destino_id')
            ->join('type_communications', 'type_communications.id', '=', 'communications.communication_type_id')
            ->selectRaw("
                COALESCE(`destino_areas`.`code`, NULLIF(`communications`.`area_destino_nombre`, ''), 'Sin destino') as `destino`,
                {$columns}
            ", $bindings);

        $this->applyStatsScope($query, 'communications', $from, $to, $areaId, $userId, ! $includeAnnulled);

        return $this->mapGroupedStats(
            $query->groupBy('destino')->orderBy('destino')->limit(15)->get(),
            fn ($row) => $row->destino,
            $codes,
        );
    }

    /**
     * @param  array<int, string>  $scopeAreaIds
     * @return array<int, array<string, int|string>>
     */
    private function getDestinoStatsForAreas(string $from, string $to, array $scopeAreaIds, bool $includeAnnulled): array
    {
        [$columns, $bindings, $codes] = $this->typeCountColumns('type_communications', 'COUNT(`c`.`id`)');
        [$start, $end] = $this->dayRange($from, $to);

        $query = Area::query()
            ->leftJoin('communications as c', function ($join) use ($start, $end, $includeAnnulled): void {
                $join->on('c.area_destino_id', '=', 'areas.id')
                    ->whereBetween('c.created_at', [$start, $end]);

                if (! $includeAnnulled) {
                    $join->where('c.status', Communication::STATUS_ACTIVE);
                }
            })
            ->leftJoin('type_communications', 'type_communications.id', '=', 'c.communication_type_id')
            ->selectRaw("`areas`.`code` as `destino`, {$columns}", $bindings)
            ->whereIn('areas.id', $scopeAreaIds);

        return $this->mapGroupedStats(
            $query->groupBy('areas.id', 'areas.code')->orderBy('areas.code')->limit(15)->get(),
            fn ($row) => $row->destino,
            $codes,
        );
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    public function getUserStats(string $from, string $to, ?string $areaId = null, ?string $userId = null, bool $includeAnnulled = false): array
    {
        [$columns, $bindings, $codes] = $this->typeCountColumns('type_communications');

        $query = Communication::query()
            ->join('users', 'users.id', '=', 'communications.user_id')
            ->join('type_communications', 'type_communications.id', '=', 'communications.communication_type_id')
            ->selectRaw("`users`.`email` as `user_email`, {$columns}", $bindings);

        $this->applyStatsScope($query, 'communications', $from, $to, $areaId, $userId, ! $includeAnnulled);

        return $this->mapGroupedStats(
            $query->groupBy('users.id', 'users.email')->orderByDesc('total')->limit(15)->get(),
            fn ($row) => Str::before($row->user_email, '@'),
            $codes,
        );
    }
}
