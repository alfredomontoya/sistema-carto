<?php

namespace App\Repositories\Eloquent;

use App\Models\Communication;
use App\Repositories\Contracts\CommunicationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentCommunicationRepository implements CommunicationRepository
{
    public function find(string $id): ?Communication
    {
        return Communication::with(['area', 'position', 'user.currentAssignment.position.area', 'recipientUser.currentAssignment.position.area'])->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Communication::query()
            ->with(['area', 'position', 'user.currentAssignment.position.area', 'recipientUser.currentAssignment.position.area'])
            ->filter($filters)
            ->orderByDesc('created_at')
            ->orderByDesc('sequence')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Communication
    {
        return Communication::create($data);
    }

    public function update(Communication $communication, array $data): Communication
    {
        $communication->update($data);

        return $communication;
    }

    public function delete(Communication $communication): void
    {
        $communication->delete();
    }

    public function deleteAll(): int
    {
        return Communication::query()->delete();
    }

    public function countForAreaYearType(string $areaId, string $type, int $year): int
    {
        return Communication::where('area_id', $areaId)
            ->where('type', $type)
            ->where('year', $year)
            ->count();
    }
}
