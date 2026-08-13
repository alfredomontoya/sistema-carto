<?php

namespace App\Services;

use App\Models\Area;
use App\Repositories\Contracts\NumberCounterRepository;

class NumberSequenceService
{
    public const TYPE_INTERNAL = 'ci';

    public const TYPE_EXTERNAL = 'of';

    public function __construct(
        private readonly NumberCounterRepository $counters,
    ) {}

    /**
     * Resolve the area that owns the numbering for a given area.
     *
     * If the area has a configured "numbering area", its counter and code are
     * used (e.g. ABOGADO -> CARTOGRAFIA numbers as ci.carto.000X). Resolution
     * is single level: the configured area is used as-is, not followed further.
     */
    public function numberingArea(Area $area): Area
    {
        $target = $area->numbering_area_id !== null
            ? ($area->numberingArea ?? Area::find($area->numbering_area_id))
            : null;

        return $target ?? $area;
    }

    /**
     * Format the correlative number: ci.carto.0001/2026
     */
    public function format(string $areaCode, string $type, int $sequence, int $year): string
    {
        return sprintf('%s.%s.%04d/%d', $type, strtolower($areaCode), $sequence, $year);
    }

    /**
     * Current state for the given area/type/year.
     *
     * @return array{sequence: int, next_number: string}
     */
    public function current(Area $area, string $type, int $year): array
    {
        $sequence = $this->counters->current($area->id, $type, $year, (bool) $area->reset_annually);

        return [
            'sequence' => $sequence,
            'next_number' => $this->format($area->code, $type, $sequence + 1, $year),
        ];
    }

    /**
     * Atomically allocate the next sequence and return the full number.
     *
     * @return array{sequence: int, number: string}
     */
    public function next(Area $area, string $type, int $year): array
    {
        $sequence = $this->counters->incrementAndGet($area->id, $type, $year, (bool) $area->reset_annually);

        return [
            'sequence' => $sequence,
            'number' => $this->format($area->code, $type, $sequence, $year),
        ];
    }

    /**
     * Reset the area counters for the given year back to zero.
     */
    public function resetForYear(Area $area, int $year): void
    {
        $this->counters->resetForArea($area->id, $year);
    }

    /**
     * Reset the counters of every area back to zero.
     */
    public function resetAll(): void
    {
        $this->counters->resetAll();
    }

    /**
     * Extract the sequence from a full number (used for repair/audit).
     */
    public function parse(string $number): ?array
    {
        if (! preg_match('/^(ci|of)\.([a-z0-9._-]+)\.(\d+)\/(\d{4})$/', $number, $m)) {
            return null;
        }

        return [
            'type' => $m[1],
            'area_code' => $m[2],
            'sequence' => (int) $m[3],
            'year' => (int) $m[4],
        ];
    }
}
