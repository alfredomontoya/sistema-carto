<?php

namespace App\Services;

use App\Models\CommunicationType;
use App\Repositories\Contracts\CommunicationTypeRepository;
use Illuminate\Database\Eloquent\Collection;

class CommunicationTypeService
{
    public function __construct(
        private readonly CommunicationTypeRepository $types,
    ) {}

    /**
     * @return Collection<int, CommunicationType>
     */
    public function all(): Collection
    {
        return $this->types->all();
    }

    /**
     * @return Collection<int, CommunicationType>
     */
    public function active(): Collection
    {
        return $this->types->active();
    }

    public function find(string $id): ?CommunicationType
    {
        return $this->types->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CommunicationType
    {
        $data['code'] = strtolower(trim((string) ($data['code'] ?? '')));

        return $this->types->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CommunicationType $type, array $data): CommunicationType
    {
        if (isset($data['code'])) {
            $data['code'] = strtolower(trim((string) $data['code']));
        }

        return $this->types->update($type, $data);
    }

    /**
     * Deletes a communication type. Refuses when it has communications or
     * counters (to never lose correlative numbers).
     *
     * @return bool true when deleted, false when blocked
     */
    public function delete(CommunicationType $type): bool
    {
        if ($type->communications()->exists()) {
            return false;
        }

        if ($type->counters()->exists()) {
            return false;
        }

        $this->types->delete($type);

        return true;
    }
}
