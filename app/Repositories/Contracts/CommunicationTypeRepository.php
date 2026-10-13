<?php

namespace App\Repositories\Contracts;

use App\Models\CommunicationType;
use Illuminate\Database\Eloquent\Collection;

interface CommunicationTypeRepository
{
    /**
     * @return Collection<int, CommunicationType>
     */
    public function all(): Collection;

    /**
     * @return Collection<int, CommunicationType>
     */
    public function active(): Collection;

    public function find(string $id): ?CommunicationType;

    public function findByCode(string $code): ?CommunicationType;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CommunicationType;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CommunicationType $type, array $data): CommunicationType;

    public function delete(CommunicationType $type): void;
}
