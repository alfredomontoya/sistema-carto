<?php

namespace App\Repositories\Contracts;

use App\Models\Communication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CommunicationRepository
{
    public function find(string $id): ?Communication;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Communication;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Communication $communication, array $data): Communication;

    public function delete(Communication $communication): void;

    /**
     * Delete every communication record and return the number deleted.
     */
    public function deleteAll(): int;

    public function countForAreaYearType(string $areaId, string $type, int $year): int;

    /**
     * @return array<int, array{month: int, ci: int, of: int, total: int}>
     */
    public function getMonthlyStats(int $year, ?string $areaId = null, ?string $userId = null): array;

    /**
     * @return array<int, array{destino: string, ci: int, of: int, total: int}>
     */
    public function getDestinoStats(string $from, string $to, ?string $areaId = null, ?string $userId = null, bool $includeAnnulled = false): array;

    /**
     * @return array<int, array{destino: string, ci: int, of: int, total: int}>
     */
    public function getUserStats(string $from, string $to, ?string $areaId = null, ?string $userId = null, bool $includeAnnulled = false): array;

    /**
     * @return array<int, int>
     */
    public function getAvailableYears(?string $areaId = null, ?string $userId = null): array;
}
