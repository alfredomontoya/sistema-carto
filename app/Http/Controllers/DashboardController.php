<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Services\CommunicationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
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

        $user = $request->user();
        $canFilterAreas = $user->hasRole('administrador');
        $areaFilter = $this->resolveAreaFilter($request, $canFilterAreas);

        $stats = $this->communications->getDashboardStats($user, $year, $areaFilter);
        $availableYears = $this->communications->getAvailableYears($user, $areaFilter);
        $isAdmin = $user->seesGlobalStats();

        [$period, $from, $to, $periodYear] = $this->resolvePeriod($request);
        $includeAnnulled = $request->boolean('annulled', false);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'selectedYear' => $year,
            'availableYears' => $availableYears,
            'isAdmin' => $isAdmin,
            'communicationTypes' => $this->communications->activeTypes(),
            'canFilterAreas' => $canFilterAreas,
            'selectedAreaId' => $areaFilter,
            'filterAreas' => $canFilterAreas
                ? Area::query()->orderBy('name')->get(['id', 'code', 'name'])->all()
                : [],
            'destinoStats' => Inertia::defer(fn () => $this->communications->getDestinoStats($user, $from, $to, $includeAnnulled, $areaFilter)),
            'userStats' => Inertia::defer(fn () => $this->communications->getUserStats($user, $from, $to, $includeAnnulled, $areaFilter)),
            'period' => $period,
            'dateFrom' => $from,
            'dateTo' => $to,
            'periodYear' => $periodYear,
            'includeAnnulled' => $includeAnnulled,
        ]);
    }

    /**
     * Resolve the admin area filter: another area id, 'all' for every
     * area, or null for the user's own scope. Only administrators may
     * filter; everyone else always sees their own area.
     */
    private function resolveAreaFilter(Request $request, bool $canFilter): ?string
    {
        if (! $canFilter) {
            return null;
        }

        $value = $request->query('area_id');

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        if ($value === 'all') {
            return 'all';
        }

        return Area::where('id', $value)->exists() ? $value : null;
    }

    /**
     * Generate the detailed communications PDF report for the
     * selected period. Streams inline so the browser shows its
     * PDF viewer preview.
     */
    public function report(Request $request): HttpResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
            'annulled' => ['sometimes', 'boolean'],
        ]);

        $from = Carbon::createFromFormat('Y-m-d', $validated['from'])->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $validated['to'])->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        $includeAnnulled = (bool) ($validated['annulled'] ?? false);
        $user = $request->user();
        $rows = $this->communications->getReportData(
            $user,
            $from->toDateString(),
            $to->toDateString(),
            $includeAnnulled,
        );

        $pdf = Pdf::loadView('reports.communications', [
            'appName' => (string) config('app.name'),
            'generatedBy' => $user->name,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'from' => $from->format('d/m/Y'),
            'to' => $to->format('d/m/Y'),
            'includeAnnulled' => $includeAnnulled,
            'areaName' => $user->currentArea?->name,
            'rows' => $rows,
            'total' => $rows->count(),
        ])->setPaper('letter', 'landscape')->setOption('isPhpEnabled', true);

        return $pdf->stream("reporte-comunicaciones-{$from->format('Ymd')}-{$to->format('Ymd')}.pdf");
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
