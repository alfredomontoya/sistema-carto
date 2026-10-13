<?php

namespace App\Services;

use App\Models\Area;
use App\Models\CommunicationType;
use App\Repositories\Contracts\NumberCounterRepository;

class NumberSequenceService
{
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
     * Whether the sequence restarts for a new year. Both the numbering area
     * and the communication type must allow it; if either opts out, the
     * sequence continues from the previous years.
     */
    public function resetsAnnually(Area $area, CommunicationType $type): bool
    {
        return (bool) $area->reset_annually && (bool) $type->reset_annually;
    }

    /**
     * Format the correlative number: ci.carto.0001/2026
     *
     * The prefix comes from the communication type code, the digit padding
     * from its `digits` setting, and an optional `format_template` may
     * override the layout with {prefix}, {area}, {seq} and {year} tokens.
     */
    public function format(CommunicationType $type, string $areaCode, int $sequence, int $year): string
    {
        $prefix = strtolower($type->code);
        $area = strtolower($areaCode);
        $padded = str_pad((string) $sequence, max(1, $type->digits), '0', STR_PAD_LEFT);

        if ($type->format_template !== null && trim($type->format_template) !== '') {
            return strtr($type->format_template, [
                '{prefix}' => $prefix,
                '{area}' => $area,
                '{seq}' => $padded,
                '{year}' => (string) $year,
            ]);
        }

        return sprintf('%s.%s.%s/%d', $prefix, $area, $padded, $year);
    }

    /**
     * Current state for the given area/type/year.
     *
     * @return array{sequence: int, next_number: string}
     */
    public function current(Area $area, CommunicationType $type, int $year): array
    {
        $sequence = $this->counters->current($area->id, $type->id, $year, $this->resetsAnnually($area, $type));

        return [
            'sequence' => $sequence,
            'next_number' => $this->format($type, $area->code, $sequence + 1, $year),
        ];
    }

    /**
     * Atomically allocate the next sequence and return the full number.
     *
     * @return array{sequence: int, number: string}
     */
    public function next(Area $area, CommunicationType $type, int $year): array
    {
        $sequence = $this->counters->incrementAndGet($area->id, $type->id, $year, $this->resetsAnnually($area, $type));

        return [
            'sequence' => $sequence,
            'number' => $this->format($type, $area->code, $sequence, $year),
        ];
    }

    /**
     * Reset the area counters for the given year back to zero.
     *
     * @param  array<int, string>  $communicationTypeIds  when empty, resets every type
     */
    public function resetForYear(Area $area, int $year, array $communicationTypeIds = []): void
    {
        $this->counters->resetForArea($area->id, $year, $communicationTypeIds);
    }

    /**
     * Current numbering overview for every area in the given year.
     *
     * @return array<int, array{area_id: string, area_name: string, area_code: string, types: array<int, array{type_id: string, code: string, name: string, current: int, issued_max: int}>}>
     */
    public function overview(int $year, ?string $search = null): array
    {
        return $this->counters->overview($year, $search);
    }

    /**
     * Set the counter of an area/type/year to an explicit value.
     */
    public function setSequence(Area $area, string $communicationTypeId, int $year, int $value): void
    {
        $this->counters->setSequence($area->id, $communicationTypeId, $year, $value);
    }

    /**
     * Reset the counters of every area back to zero.
     */
    public function resetAll(): void
    {
        $this->counters->resetAll();
    }

    /**
     * Extract the prefix/area/sequence/year from a full number
     * (used for repair/audit).
     */
    public function parse(string $number): ?array
    {
        if (! preg_match('/^([a-z0-9_-]+)\.([a-z0-9._-]+)\.(\d+)\/(\d{4})$/', $number, $m)) {
            return null;
        }

        return [
            'prefix' => $m[1],
            'area_code' => $m[2],
            'sequence' => (int) $m[3],
            'year' => (int) $m[4],
        ];
    }
}
