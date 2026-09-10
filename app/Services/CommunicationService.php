<?php

namespace App\Services;

use App\Models\Communication;
use App\Models\User;
use App\Repositories\Contracts\AreaRepository;
use App\Repositories\Contracts\CommunicationRepository;
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
     *   ci: array{sequence: int, next_number: string|null},
     *   of: array{sequence: int, next_number: string|null},
     *   numbering_area: array{code: string, name: string}|null
     * }
     */
    public function countersFor(User $user, int $year): array
    {
        $user->loadMissing('currentAssignment.position.area');

        $area = $user->currentArea;

        if ($area === null) {
            return [
                'ci' => ['sequence' => 0, 'next_number' => null],
                'of' => ['sequence' => 0, 'next_number' => null],
                'numbering_area' => null,
            ];
        }

        $numbering = $this->numbers->numberingArea($area);

        return [
            'ci' => $this->numbers->current($numbering, NumberSequenceService::TYPE_INTERNAL, $year),
            'of' => $this->numbers->current($numbering, NumberSequenceService::TYPE_EXTERNAL, $year),
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
        $type = $data['type'];

        return DB::transaction(function () use ($user, $data, $file, $area, $year, $type): Communication {
            $numbering = $this->numbers->numberingArea($area);
            $next = $this->numbers->next($numbering, $type, $year);

            $payload = [
                'type' => $type,
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

    public function getDashboardStats(User $user, int $year): array
    {
        $cacheKey = $this->buildStatsCacheKey($user, $year);

        return Cache::remember($cacheKey, 300, function () use ($user, $year) {
            $isAdmin = $user->seesGlobalStats();

            $areaId = $isAdmin ? null : $user->currentArea?->id;
            $userId = $isAdmin ? null : $user->id;

            return $this->communications->getMonthlyStats($year, $areaId, $userId);
        });
    }

    public function getDestinoStats(User $user, string $from, string $to, bool $includeAnnulled = false): array
    {
        $isAdmin = $user->seesGlobalStats();
        $scopeAreaIds = $this->resolveDestinoScope($user);
        $scope = $scopeAreaIds === null
            ? ($isAdmin ? 'global' : "area_{$user->currentArea?->id}_user_{$user->id}")
            : 'areas_'.md5(implode(',', [...$scopeAreaIds]));
        $state = $includeAnnulled ? 'all' : 'active';
        $cacheKey = "dashboard:destino:v{$this->statsVersion()}:{$scope}:{$from}_{$to}:{$state}";

        return Cache::remember($cacheKey, 300, function () use ($user, $from, $to, $isAdmin, $scopeAreaIds, $includeAnnulled) {
            $areaId = $isAdmin ? null : $user->currentArea?->id;
            $userId = $isAdmin ? null : $user->id;

            return $this->communications->getDestinoStats($from, $to, $areaId, $userId, $includeAnnulled, $scopeAreaIds);
        });
    }

    /**
     * Areas visible in the destino stats: every area for administrators,
     * the own subtree for jefes, null (legacy data-driven rows) for the rest.
     *
     * @return array<int, string>|null
     */
    private function resolveDestinoScope(User $user): ?array
    {
        if ($user->hasRole('administrador')) {
            return $this->areaRepository->all()->pluck('id')->all();
        }

        if ($user->hasRole('jefe')) {
            $user->loadMissing('currentAssignment.position.area');
            $area = $user->currentArea;

            if ($area === null) {
                return [];
            }

            return array_merge([$area->id], $this->areaRepository->descendantIds($area));
        }

        return null;
    }

    public function getUserStats(User $user, string $from, string $to, bool $includeAnnulled = false): array
    {
        $isAdmin = $user->seesGlobalStats();
        $scope = $isAdmin ? 'global' : "area_{$user->currentArea?->id}_user_{$user->id}";
        $state = $includeAnnulled ? 'all' : 'active';
        $cacheKey = "dashboard:usuarios:v{$this->statsVersion()}:{$scope}:{$from}_{$to}:{$state}";

        return Cache::remember($cacheKey, 300, function () use ($user, $from, $to, $isAdmin, $includeAnnulled) {
            $areaId = $isAdmin ? null : $user->currentArea?->id;
            $userId = $isAdmin ? null : $user->id;

            return $this->communications->getUserStats($from, $to, $areaId, $userId, $includeAnnulled);
        });
    }

    public function getAvailableYears(User $user): array
    {
        $isAdmin = $user->seesGlobalStats();
        $scope = $isAdmin ? 'global' : "area_{$user->currentArea?->id}_user_{$user->id}";
        $cacheKey = "dashboard:years:v{$this->statsVersion()}:{$scope}";

        return Cache::remember($cacheKey, 3600, function () use ($user, $isAdmin) {
            $areaId = $isAdmin ? null : $user->currentArea?->id;
            $userId = $isAdmin ? null : $user->id;

            return $this->communications->getAvailableYears($areaId, $userId);
        });
    }

    private function buildStatsCacheKey(User $user, int $year): string
    {
        $isAdmin = $user->seesGlobalStats();
        $scope = $isAdmin ? 'global' : "area_{$user->currentArea?->id}_user_{$user->id}";
        return "dashboard:stats:v{$this->statsVersion()}:{$scope}:{$year}";
    }

    private function statsVersion(): int
    {
        return (int) Cache::rememberForever(self::STATS_CACHE_VERSION_KEY, fn (): int => 1);
    }

    private function invalidateDashboardStats(): void
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
