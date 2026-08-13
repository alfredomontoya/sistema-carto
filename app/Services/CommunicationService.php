<?php

namespace App\Services;

use App\Models\Communication;
use App\Models\User;
use App\Repositories\Contracts\CommunicationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommunicationService
{
    public const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'webp',
    ];

    public function __construct(
        private readonly CommunicationRepository $communications,
        private readonly NumberSequenceService $numbers,
    ) {}

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
                'status' => Communication::STATUS_ACTIVE,
            ];

            if ($file !== null) {
                $this->storeFile($file, $payload);
            }

            try {
                return $this->communications->create($payload);
            } catch (\Throwable $e) {
                if (($payload['file_path'] ?? null) !== null) {
                    Storage::disk('public')->delete($payload['file_path']);
                }

                throw $e;
            }
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Communication $communication, array $data, ?UploadedFile $file = null): Communication
    {
        $payload = [
            'reference' => $data['reference'],
            'recipient_name' => $data['recipient_name'],
            'recipient_position' => $data['recipient_position'] ?? null,
            'recipient_user_id' => $data['recipient_user_id'] ?? null,
        ];

        if ($file !== null) {
            $this->deleteFile($communication);
            $this->storeFile($file, $payload);
        } elseif (($data['remove_file'] ?? false) === true) {
            $this->deleteFile($communication);
            $payload['file_path'] = null;
            $payload['file_name'] = null;
        }

        return $this->communications->update($communication, $payload);
    }

    public function annul(Communication $communication): Communication
    {
        return $this->communications->update($communication, [
            'status' => Communication::STATUS_ANNULLED,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->communications->paginate($filters, $perPage);
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
