<?php

namespace App\Repositories\Eloquent;

use App\Models\Communication;
use App\Repositories\Contracts\CommunicationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
     * @return array<int, array{month: int, ci: int, of: int, total: int}>
     */
    public function getMonthlyStats(int $year, ?string $areaId = null, ?string $userId = null): array
    {
        $query = Communication::query()
            ->selectRaw('
                MONTH(`created_at`) as `month`,
                SUM(CASE WHEN `type` = ? THEN 1 ELSE 0 END) as `ci`,
                SUM(CASE WHEN `type` = ? THEN 1 ELSE 0 END) as `of`,
                COUNT(*) as `total`
            ', [Communication::TYPE_INTERNAL, Communication::TYPE_EXTERNAL])
            ->whereYear('created_at', $year)
            ->where('status', Communication::STATUS_ACTIVE);

        if ($areaId) $query->where('area_id', $areaId);
        if ($userId) $query->where('user_id', $userId);

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
            ->selectRaw('DISTINCT YEAR(`created_at`) as `year`')
            ->where('status', Communication::STATUS_ACTIVE)
            ->orderByDesc('year');

        if ($areaId) $query->where('area_id', $areaId);
        if ($userId) $query->where('user_id', $userId);

        return $query->pluck('year')->all();
    }

    /**
     * @return array<int, array{destino: string, ci: int, of: int, total: int}>
     */
    public function getDestinoStats(string $from, string $to, ?string $areaId = null, ?string $userId = null): array
    {
        $query = Communication::query()
            ->selectRaw("
                COALESCE(NULLIF(`area_destino_nombre`, ''), 'Sin destino') as `destino`,
                SUM(CASE WHEN `type` = ? THEN 1 ELSE 0 END) as `ci`,
                SUM(CASE WHEN `type` = ? THEN 1 ELSE 0 END) as `of`,
                COUNT(*) as `total`
            ", [Communication::TYPE_INTERNAL, Communication::TYPE_EXTERNAL])
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->where('status', Communication::STATUS_ACTIVE);

        if ($areaId) $query->where('area_id', $areaId);
        if ($userId) $query->where('user_id', $userId);

        return $query->groupBy('destino')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn ($row) => [
                'destino' => (string) $row->destino,
                'ci' => (int) $row->ci,
                'of' => (int) $row->of,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }
}
