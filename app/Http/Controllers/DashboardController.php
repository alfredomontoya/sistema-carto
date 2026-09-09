<?php

namespace App\Http\Controllers;

use App\Services\CommunicationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public const PERIOD_TODAY = 'hoy';

    public const PERIOD_YESTERDAY = 'ayer';

    public const PERIOD_WEEK = 'semana';

    public const PERIOD_MONTH = 'mes';

    public const PERIOD_YEAR = 'año';

    public const PERIOD_RANGE = 'rango';

    public function __construct(
        private readonly CommunicationService $communications,
    ) {}

    public function index(Request $request): Response
    {
        $year = $request->integer('year', now()->year);
        $year = max(2020, min($year, now()->year + 1));

        $stats = $this->communications->getDashboardStats($request->user(), $year);
        $availableYears = $this->communications->getAvailableYears($request->user());
        $isAdmin = $request->user()->seesGlobalStats();

        [$period, $from, $to, $periodYear] = $this->resolvePeriod($request);
        $includeAnnulled = $request->boolean('annulled', false);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'selectedYear' => $year,
            'availableYears' => $availableYears,
            'isAdmin' => $isAdmin,
            'destinoStats' => Inertia::defer(fn () => $this->communications->getDestinoStats($request->user(), $from, $to, $includeAnnulled)),
            'userStats' => Inertia::defer(fn () => $this->communications->getUserStats($request->user(), $from, $to, $includeAnnulled)),
            'period' => $period,
            'dateFrom' => $from,
            'dateTo' => $to,
            'periodYear' => $periodYear,
            'includeAnnulled' => $includeAnnulled,
        ]);
    }

    /**
     * @return array{string, string, string, int}
     */
    private function resolvePeriod(Request $request): array
    {
        $period = $request->string('period', self::PERIOD_TODAY)->toString();

        if (! in_array($period, [self::PERIOD_TODAY, self::PERIOD_YESTERDAY, self::PERIOD_WEEK, self::PERIOD_MONTH, self::PERIOD_YEAR, self::PERIOD_RANGE], true)) {
            $period = self::PERIOD_TODAY;
        }

        $today = Carbon::today();
        $periodYear = (int) $request->integer('period_year', $today->year);
        $periodYear = max(2020, min($periodYear, $today->year + 1));

        $ref = $periodYear === $today->year
            ? $today->copy()
            : Carbon::create($periodYear, $today->month, min($today->day, Carbon::create($periodYear, $today->month, 1)->daysInMonth));

        $range = match ($period) {
            self::PERIOD_YESTERDAY => [$ref->copy()->subDay(), $ref->copy()->subDay()],
            self::PERIOD_WEEK => [$ref->copy()->startOfWeek(Carbon::MONDAY), $ref->copy()->endOfWeek(Carbon::SUNDAY)],
            self::PERIOD_MONTH => [$ref->copy()->startOfMonth(), $ref->copy()->endOfMonth()],
            self::PERIOD_YEAR => [
                $ref->copy()->startOfYear(),
                $periodYear === $today->year ? $today->copy() : $ref->copy()->endOfYear(),
            ],
            self::PERIOD_RANGE => [$this->parseDate($request->query('from')), $this->parseDate($request->query('to'))],
            default => [$ref->copy(), $ref->copy()],
        };

        [$from, $to] = $range;
        $from ??= $ref->copy();
        $to ??= $ref->copy();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$period, $from->toDateString(), $to->toDateString(), $periodYear];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date === false ? null : $date->startOfDay();
    }
}
