<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Communication;
use App\Models\User;
use App\Repositories\Contracts\AreaRepository;
use App\Repositories\Contracts\CommunicationRepository;
use App\Repositories\Contracts\CommunicationTypeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommunicationService
{
    public const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'webp',
    ];

    private const STATS_CACHE_VERSION_KEY = 'dashboard:stats:version';

    public function __construct(
        private readonly CommunicationRepository $communications,
        private readonly NumberSequenceService $numbers,
        private readonly AreaService $areas,
        private readonly AreaRepository $areaRepository,
        private readonly CommunicationTypeRepository $types,
    ) {}

    /**
     * Resolve the destination area, falling back to the default OTRO
     * area when none is registered. Keeps a typed free-text name,
     * or labels it OTRO when empty.
     *
     * @param  array<string, mixed>  $payload
     */
    private function resolveDestino(array &$payload): void
    {
        if (! empty($payload['area_destino_id'])) {
            return;
        }

        $default = $this->areas->defaultArea();

        $payload['area_destino_id'] = $default->id;

        if (empty($payload['area_destino_nombre'])) {
            $payload['area_destino_nombre'] = $default->name;
        }
    }

    /**
     * @return array{
     *   types: array<int, array{id: string, code: string, name: string, sequence: int, next_number: string}>,
     *   numbering_area: array{code: string, name: string}|null
     * }
     */
    public function countersFor(User $user, int $year): array
    {
        $user->loadMissing('currentAssignment.position.area');

        $area = $user->currentArea;

        if ($area === null) {
            return [
                'types' => [],
                'numbering_area' => null,
            ];
        }

        $numbering = $this->numbers->numberingArea($area);

        $types = $this->types->active()->map(function ($type) use ($numbering, $year) {
            $current = $this->numbers->current($numbering, $type, $year);

            return [
                'id' => $type->id,
                'code' => $type->code,
                'name' => $type->name,
                'sequence' => $current['sequence'],
                'next_number' => $current['next_number'],
            ];
        })->all();

        return [
            'types' => $types,
            'numbering_area' => $numbering->id !== $area->id
                ? ['code' => $numbering->code, 'name' => $numbering->name]
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data, ?UploadedFile $file = null): Communication
    {
        $user->loadMissing('currentAssignment.position.area');

        $area = $user->currentArea;
        if ($area === null) {
            throw new \InvalidArgumentException('El usuario no tiene un área asignada.');
        }

        $year = now()->year;
        $type = $this->types->find($data['communication_type_id'] ?? '');

        if ($type === null || ! $type->is_active) {
            throw new \InvalidArgumentException('El tipo de comunicación no existe o está inactivo.');
        }

        return DB::transaction(function () use ($user, $data, $file, $area, $year, $type): Communication {
            $numbering = $this->numbers->numberingArea($area);
            $next = $this->numbers->next($numbering, $type, $year);

            $payload = [
                'communication_type_id' => $type->id,
                'number' => $next['number'],
                'year' => $year,
                'sequence' => $next['sequence'],
                'area_id' => $area->id,
                'user_id' => $user->id,
                'position_id' => $user->currentAssignment?->position_id,
                'reference' => $data['reference'],
                'recipient_name' => $data['recipient_name'],
                'recipient_position' => $data['recipient_position'] ?? null,
                'recipient_user_id' => $data['recipient_user_id'] ?? null,
                'area_destino_id' => $data['area_destino_id'] ?? null,
                'area_destino_nombre' => $data['area_destino_nombre'] ?? null,
                'status' => Communication::STATUS_ACTIVE,
            ];

            $this->resolveDestino($payload);

            if ($file !== null) {
                $this->storeFile($file, $payload);
            }

            try {
                $communication = $this->communications->create($payload);
            } catch (\Throwable $e) {
                if (($payload['file_path'] ?? null) !== null) {
                    Storage::disk('public')->delete($payload['file_path']);
                }

                throw $e;
            }

            $this->invalidateDashboardStats();

            return $communication;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Communication $communication, array $data, ?UploadedFile $file = null): Communication
    {
        return DB::transaction(function () use ($communication, $data, $file): Communication {
            $payload = [
                'reference' => $data['reference'],
                'recipient_name' => $data['recipient_name'],
                'recipient_position' => $data['recipient_position'] ?? null,
                'recipient_user_id' => $data['recipient_user_id'] ?? null,
                'area_destino_id' => $data['area_destino_id'] ?? null,
                'area_destino_nombre' => $data['area_destino_nombre'] ?? null,
            ];

            $this->resolveDestino($payload);

            if ($file !== null) {
                $this->deleteFile($communication);
                $this->storeFile($file, $payload);
            } elseif (($data['remove_file'] ?? false) === true) {
                $this->deleteFile($communication);
                $payload['file_path'] = null;
                $payload['file_name'] = null;
            }

            return $this->communications->update($communication, $payload);
        });
    }

    public function annul(Communication $communication): Communication
    {
        $result = $this->communications->update($communication, [
            'status' => Communication::STATUS_ANNULLED,
        ]);

        $this->invalidateDashboardStats();

        return $result;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->communications->paginate($filters, $perPage);
    }

    public function getDashboardStats(User $user, int $year, ?string $areaFilter = null): array
    {
        [$areaIds, $global] = $this->resolveStatsScopeWithFilter($user, $areaFilter);
        $scope = $this->statsScopeKeyFor($areaIds, $global);
        $cacheKey = "dashboard:stats:v{$this->statsVersion()}:{$scope}:{$year}";

        return Cache::remember($cacheKey, 300, function () use ($areaIds, $global, $year) {
            if (! $global && $areaIds === null) {
                return [];
            }

            return $this->communications->getMonthlyStats($year, $global ? null : $areaIds, null);
        });
    }

    public function getDestinoStats(User $user, string $from, string $to, bool $includeAnnulled = false, ?string $areaFilter = null): array
    {
        [$areaIds, $global] = $this->resolveStatsScopeWithFilter($user, $areaFilter);
        $areaId = $areaIds[0] ?? null;
        $scopeAreaIds = $this->resolveDestinoScope($user);
        $scope = $scopeAreaIds === null
            ? $this->statsScopeKeyFor($areaIds, $global)
            : 'areas_'.md5(implode(',', [...$scopeAreaIds]));
        $state = $includeAnnulled ? 'all' : 'active';
        $cacheKey = "dashboard:destino:v{$this->statsVersion()}:{$scope}:{$from}_{$to}:{$state}";

        return Cache::remember($cacheKey, 300, function () use ($from, $to, $global, $areaId, $scopeAreaIds, $includeAnnulled) {
            if (! $global && $areaId === null) {
                return [];
            }

            return $this->communications->getDestinoStats($from, $to, $global ? null : $areaId, null, $includeAnnulled, $scopeAreaIds);
        });
    }

    /**
     * Areas visible in the destino stats: every area for area-less
     * administrators, the own subtree for jefes, null (area-wide
     * legacy rows) for the rest.
     *
     * @return array<int, string>|null
     */
    private function resolveDestinoScope(User $user): ?array
    {
        $user->loadMissing('currentAssignment.position.area');
        $area = $user->currentArea;

        if ($area === null) {
            return $user->hasRole('administrador')
                ? $this->areaRepository->all()->pluck('id')->all()
                : [];
        }

        if ($user->hasRole('jefe')) {
            return $this->areaRepository->descendantIdsIncluding($area);
        }

        return null;
    }

    public function getUserStats(User $user, string $from, string $to, bool $includeAnnulled = false, ?string $areaFilter = null): array
    {
        [$areaIds, $global] = $this->resolveStatsScopeWithFilter($user, $areaFilter);
        $scope = $this->statsScopeKeyFor($areaIds, $global);
        $state = $includeAnnulled ? 'all' : 'active';
        $cacheKey = "dashboard:usuarios:v{$this->statsVersion()}:{$scope}:{$from}_{$to}:{$state}";

        return Cache::remember($cacheKey, 300, function () use ($from, $to, $global, $areaIds, $includeAnnulled) {
            if (! $global && $areaIds === null) {
                return [];
            }

            return $this->communications->getUserStats($from, $to, $global ? null : $areaIds, null, $includeAnnulled);
        });
    }

    public function getAvailableYears(User $user, ?string $areaFilter = null): array
    {
        [$areaIds, $global] = $this->resolveStatsScopeWithFilter($user, $areaFilter);
        $scope = $this->statsScopeKeyFor($areaIds, $global);
        $cacheKey = "dashboard:years:v{$this->statsVersion()}:{$scope}";

        return Cache::remember($cacheKey, 3600, function () use ($global, $areaIds) {
            if (! $global && $areaIds === null) {
                return [];
            }

            return $this->communications->getAvailableYears($global ? null : $areaIds, null);
        });
    }

    /**
     * Detailed rows for the PDF report, scoped to the user's area
     * subtree (global only for area-less admins, empty for area-less
     * others).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Communication>
     */
    public function getReportData(User $user, string $from, string $to, bool $includeAnnulled = false): \Illuminate\Database\Eloquent\Collection
    {
        [$areaIds, $global] = $this->resolveStatsScope($user);

        if (! $global && $areaIds === null) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return $this->communications->reportData($from, $to, $global ? null : $areaIds, $includeAnnulled);
    }

    /**
     * Active communication types for dynamic dashboard stats
     * (cards, charts and tables render one series per type).
     *
     * @return array<int, array{id: string, code: string, name: string}>
     */
    public function activeTypes(): array
    {
        return $this->types->active()->map(fn ($type) => [
            'id' => $type->id,
            'code' => $type->code,
            'name' => $type->name,
        ])->all();
    }

    /**
     * Dashboard scope: the user's own area subtree when they have one
     * (admins included), global only for area-less admins, empty for
     * area-less basic users.
     *
     * An administrator may override the scope with an area filter:
     * another area id (its subtree), or 'all' for every area.
     *
     * @return array{array<int, string>|null, bool} [areaIds, global]
     */
    private function resolveStatsScope(User $user): array
    {
        $user->loadMissing('currentAssignment.position.area');
        $areaIds = $this->subtreeIds($user->currentArea);

        if ($areaIds !== null) {
            return [$areaIds, false];
        }

        return [null, $user->seesGlobalStats()];
    }

    /**
     * @return array{array<int, string>|null, bool} [areaIds, global]
     */
    private function resolveStatsScopeWithFilter(User $user, ?string $areaFilter): array
    {
        if ($areaFilter !== null && $user->hasRole('administrador')) {
            if ($areaFilter === 'all') {
                return [null, true];
            }

            $area = $this->areaRepository->find($areaFilter);

            if ($area !== null) {
                return [$this->areaRepository->descendantIdsIncluding($area), false];
            }
        }

        return $this->resolveStatsScope($user);
    }

    /**
     * @return array<int, string>|null
     */
    private function subtreeIds(?Area $area): ?array
    {
        return $area !== null
            ? $this->areaRepository->descendantIdsIncluding($area)
            : null;
    }

    private function statsScopeKey(User $user): string
    {
        [$areaIds, $global] = $this->resolveStatsScope($user);

        return $this->statsScopeKeyFor($areaIds, $global);
    }

    /**
     * @param  array<int, string>|null  $areaIds
     */
    private function statsScopeKeyFor(?array $areaIds, bool $global): string
    {
        if ($global) {
            return 'global';
        }

        return $areaIds !== null && $areaIds !== []
            ? 'areas_'.md5(implode(',', $areaIds))
            : 'none';
    }

    private function buildStatsCacheKey(User $user, int $year): string
    {
        $scope = $this->statsScopeKey($user);
        return "dashboard:stats:v{$this->statsVersion()}:{$scope}:{$year}";
    }

    private function statsVersion(): int
    {
        return (int) Cache::rememberForever(self::STATS_CACHE_VERSION_KEY, fn (): int => 1);
    }

    public function invalidateDashboardStats(): void
    {
        if (! Cache::has(self::STATS_CACHE_VERSION_KEY)) {
            Cache::forever(self::STATS_CACHE_VERSION_KEY, 1);
        }

        Cache::increment(self::STATS_CACHE_VERSION_KEY);
    }

    /**
     * Yesterday's created communications ranking for the user.
     *
     * @return array{count: int, max: int, is_top: bool}
     */
    public function yesterdayCreatorStats(User $user): array
    {
        $yesterday = now()->subDay()->toDateString();

        $max = (int) Communication::whereDate('created_at', $yesterday)
            ->groupBy('user_id')
            ->selectRaw('COUNT(*) as total')
            ->orderByDesc('total')
            ->limit(1)
            ->value('total');

        $mine = Communication::where('user_id', $user->id)
            ->whereDate('created_at', $yesterday)
            ->count();

        return [
            'count' => $mine,
            'max' => $max,
            'is_top' => $max > 0 && $mine >= $max,
        ];
    }

    /**
     * Whether the user tied or led yesterday's created communications ranking.
     */
    public function wasTopCreatorYesterday(User $user): bool
    {
        return $this->yesterdayCreatorStats($user)['is_top'];
    }

    public function find(string $id): ?Communication
    {
        return $this->communications->find($id);
    }

    public function deleteFile(Communication $communication): void
    {
        if ($communication->file_path !== null) {
            Storage::disk('public')->delete($communication->file_path);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeFile(UploadedFile $file, array &$payload): void
    {
        $name = $file->getClientOriginalName();
        $path = $file->store('communications', 'public');

        $payload['file_path'] = $path;
        $payload['file_name'] = $name;
    }
}
