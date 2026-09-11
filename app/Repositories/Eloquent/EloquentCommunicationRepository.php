<?php

namespace App\Repositories\Eloquent;

use App\Models\Area;
use App\Models\Communication;
use App\Repositories\Contracts\CommunicationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentCommunicationRepository implements CommunicationRepository
{
    public function find(string $id): ?Communication
    {
        return Communication::with(['area', 'areaDestino', 'position', 'user.currentAssignment.position.area', 'recipientUser.currentAssignment.position.area'])->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Communication::query()
            ->with(['area', 'position', 'user.currentAssignment.position.area', 'recipientUser.currentAssignment.position.area'])
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

    public function countForAreaYearType(string $areaId, string $type, int $year): int
    {
        return Communication::where('area_id', $areaId)
            ->where('type', $type)
            ->where('year', $year)
            ->count();
    }

    /**
     * @return array{string, array<int, string>}
     */
    private function typeCountColumns(string $table = ''): array
    {
        $type = $table === '' ? '`type`' : "`{$table}`.`type`";
        $count = $table === '' ? 'COUNT(*)' : "COUNT(`{$table}`.`id`)";

        $raw = "SUM(CASE WHEN {$type} = ? THEN 1 ELSE 0 END) as `ci`, "
            ."SUM(CASE WHEN {$type} = ? THEN 1 ELSE 0 END) as `of`, "
            ."{$count} as `total`";

        return [$raw, [Communication::TYPE_INTERNAL, Communication::TYPE_EXTERNAL]];
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
     * @param  Collection<int, mixed>  $rows
     * @return array<int, array{destino: string, ci: int, of: int, total: int}>
     */
    private function mapGroupedStats(Collection $rows, callable $label): array
    {
        return $rows->map(fn ($row) => [
            'destino' => (string) $label($row),
            'ci' => (int) $row->ci,
            'of' => (int) $row->of,
            'total' => (int) $row->total,
        ])->values()->all();
    }

    /**
     * @return array<int, array{month: int, ci: int, of: int, total: int}>
     */
    public function getMonthlyStats(int $year, ?string $areaId = null, ?string $userId = null): array
    {
        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end = Carbon::create($year, 12, 31)->endOfDay();

        $monthExpr = DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', `created_at`) AS INTEGER)"
            : 'MONTH(`created_at`)';

        [$columns, $bindings] = $this->typeCountColumns();

        $query = Communication::query()
            ->selectRaw("{$monthExpr} as `month`, ".$columns, $bindings);

        $this->applyStatsScope($query, '', $start, $end, $areaId, $userId, true);

        return $query->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month')
            ->map(fn ($row) => [
                'month' => (int) $row->month,
                'ci' => (int) $row->ci,
                'of' => (int) $row->of,
                'total' => (int) $row->total,
            ])
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
     * @return array<int, array{destino: string, ci: int, of: int, total: int}>
     */
    public function getDestinoStats(string $from, string $to, ?string $areaId = null, ?string $userId = null, bool $includeAnnulled = false, ?array $scopeAreaIds = null): array
    {
        if ($scopeAreaIds !== null) {
            return $this->getDestinoStatsForAreas($from, $to, $scopeAreaIds, $includeAnnulled);
        }

        [$columns, $bindings] = $this->typeCountColumns('communications');

        $query = Communication::query()
            ->leftJoin('areas as destino_areas', 'destino_areas.id', '=', 'communications.area_destino_id')
            ->selectRaw("
                COALESCE(`destino_areas`.`code`, NULLIF(`communications`.`area_destino_nombre`, ''), 'Sin destino') as `destino`,
                {$columns}
            ", $bindings);

        $this->applyStatsScope($query, 'communications', $from, $to, $areaId, $userId, ! $includeAnnulled);

        return $this->mapGroupedStats(
            $query->groupBy('destino')->orderBy('destino')->limit(15)->get(),
            fn ($row) => $row->destino,
        );
    }

    /**
     * @param  array<int, string>  $scopeAreaIds
     * @return array<int, array{destino: string, ci: int, of: int, total: int}>
     */
    private function getDestinoStatsForAreas(string $from, string $to, array $scopeAreaIds, bool $includeAnnulled): array
    {
        [$columns, $bindings] = $this->typeCountColumns('c');
        [$start, $end] = $this->dayRange($from, $to);

        $query = Area::query()
            ->leftJoin('communications as c', function ($join) use ($start, $end, $includeAnnulled): void {
                $join->on('c.area_destino_id', '=', 'areas.id')
                    ->whereBetween('c.created_at', [$start, $end]);

                if (! $includeAnnulled) {
                    $join->where('c.status', Communication::STATUS_ACTIVE);
                }
            })
            ->selectRaw("`areas`.`code` as `destino`, {$columns}", $bindings)
            ->whereIn('areas.id', $scopeAreaIds);

        return $this->mapGroupedStats(
            $query->groupBy('areas.id', 'areas.code')->orderBy('areas.code')->limit(15)->get(),
            fn ($row) => $row->destino,
        );
    }

    /**
     * @return array<int, array{destino: string, ci: int, of: int, total: int}>
     */
    public function getUserStats(string $from, string $to, ?string $areaId = null, ?string $userId = null, bool $includeAnnulled = false): array
    {
        [$columns, $bindings] = $this->typeCountColumns('communications');

        $query = Communication::query()
            ->join('users', 'users.id', '=', 'communications.user_id')
            ->selectRaw("`users`.`email` as `user_email`, {$columns}", $bindings);

        $this->applyStatsScope($query, 'communications', $from, $to, $areaId, $userId, ! $includeAnnulled);

        return $this->mapGroupedStats(
            $query->groupBy('users.id', 'users.email')->orderByDesc('total')->limit(15)->get(),
            fn ($row) => Str::before($row->user_email, '@'),
        );
    }
}
