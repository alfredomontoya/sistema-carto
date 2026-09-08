<?php

namespace App\Repositories\Contracts;

interface NumberCounterRepository
{
    /**
     * @param  bool  $resetAnnually  when false, a missing counter continues from
     *                               the last sequence of previous years
     */
    public function current(string $areaId, string $type, int $year, bool $resetAnnually = true): int;

    /**
     * Atomically increments the counter and returns the new sequence.
     *
     * @param  bool  $resetAnnually  when false, a missing counter starts from the
     *                               last sequence of previous years
     */
    public function incrementAndGet(string $areaId, string $type, int $year, bool $resetAnnually = true): int;

    /**
     * Reset the counters of an area for a given year back to zero.
     *
     * @param  array<int, string>  $types  when empty, resets every type
     */
    public function resetForArea(string $areaId, int $year, array $types = []): void;

    /**
     * Reset all counters of every area, year and type back to zero.
     */
    public function resetAll(): void;

    /**
     * Current numbering overview for every area in the given year.
     *
     * @return array<int, array{area_id: string, area_name: string, area_code: string, ci_current: int, of_current: int, ci_issued_max: int, of_issued_max: int}>
     */
    public function overview(int $year, ?string $search = null): array;

    /**
     * Set the counter of an area/type/year to an explicit value,
     * creating the row when it does not exist.
     */
    public function setSequence(string $areaId, string $type, int $year, int $value): void;
}
