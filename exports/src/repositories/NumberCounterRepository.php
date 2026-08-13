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
}
