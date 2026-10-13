<?php

namespace App\Repositories\Eloquent;

use App\Models\CommunicationType;
use App\Repositories\Contracts\CommunicationTypeRepository;
use Illuminate\Database\Eloquent\Collection;

class EloquentCommunicationTypeRepository implements CommunicationTypeRepository
{
    public function all(): Collection
    {
        return CommunicationType::ordered()->get();
    }

    public function active(): Collection
    {
        return CommunicationType::active()->ordered()->get();
    }

    public function find(string $id): ?CommunicationType
    {
        return CommunicationType::find($id);
    }

    public function findByCode(string $code): ?CommunicationType
    {
        return CommunicationType::where('code', strtolower($code))->first();
    }

    public function create(array $data): CommunicationType
    {
        return CommunicationType::create($data);
    }

    public function update(CommunicationType $type, array $data): CommunicationType
    {
        $type->update($data);

        return $type;
    }

    public function delete(CommunicationType $type): void
    {
        $type->delete();
    }
}
