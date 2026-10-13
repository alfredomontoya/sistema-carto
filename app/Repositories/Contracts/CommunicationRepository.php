<?php

namespace App\Repositories\Contracts;

use App\Models\Communication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

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

    public function countForAreaYearType(string $areaId, string $communicationTypeId, int $year): int;

    /**
     * @return array<int, array<string, int>>
     */
    public function getMonthlyStats(int $year, ?string $areaId = null, ?string $userId = null): array;

    /**
     * @param  array<int, string>|null  $scopeAreaIds  when given, every listed area
     *                                                  appears even with zero counts
     * @return array<int, array<string, int|string>>
     */
    public function getDestinoStats(string $from, string $to, ?string $areaId = null, ?string $userId = null, bool $includeAnnulled = false, ?array $scopeAreaIds = null): array;

    /**
     * @return array<int, array<string, int|string>>
     */
    public function getUserStats(string $from, string $to, ?string $areaId = null, ?string $userId = null, bool $includeAnnulled = false): array;

    /**
     * @return array<int, int>
     */
    public function getAvailableYears(?string $areaId = null, ?string $userId = null): array;

    /**
     * Detailed rows for the PDF report, ordered chronologically.
     *
     * @return Collection<int, Communication>
     */
    public function reportData(string $from, string $to, ?string $areaId = null, bool $includeAnnulled = false): Collection;
}
