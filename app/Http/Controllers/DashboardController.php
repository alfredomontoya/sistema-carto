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
        $isAdmin = $request->user()->can('manage areas') || $request->user()->can('manage users') || $request->user()->can('manage settings');

        [$period, $from, $to] = $this->resolvePeriod($request);
        $destinoStats = $this->communications->getDestinoStats($request->user(), $from, $to);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'selectedYear' => $year,
            'availableYears' => $availableYears,
            'isAdmin' => $isAdmin,
            'destinoStats' => $destinoStats,
            'period' => $period,
            'dateFrom' => $from,
            'dateTo' => $to,
        ]);
    }

    /**
     * @return array{string, string, string}
     */
    private function resolvePeriod(Request $request): array
    {
        $period = $request->string('period', self::PERIOD_TODAY)->toString();

        if (! in_array($period, [self::PERIOD_TODAY, self::PERIOD_YESTERDAY, self::PERIOD_WEEK, self::PERIOD_MONTH, self::PERIOD_RANGE], true)) {
            $period = self::PERIOD_TODAY;
        }

        $today = Carbon::today();

        $range = match ($period) {
            self::PERIOD_YESTERDAY => [$today->copy()->subDay(), $today->copy()->subDay()],
            self::PERIOD_WEEK => [$today->copy()->startOfWeek(Carbon::MONDAY), $today->copy()->endOfWeek(Carbon::SUNDAY)],
            self::PERIOD_MONTH => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            self::PERIOD_RANGE => [$this->parseDate($request->query('from')), $this->parseDate($request->query('to'))],
            default => [$today->copy(), $today->copy()],
        };

        [$from, $to] = $range;
        $from ??= $today->copy();
        $to ??= $today->copy();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$period, $from->toDateString(), $to->toDateString()];
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
