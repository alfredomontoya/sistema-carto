<?php

namespace App\Repositories\Contracts;

use App\Models\Area;
use Illuminate\Database\Eloquent\Collection;

interface AreaRepository
{
    /**
     * @return Collection<int, Area>
     */
    public function all(): Collection;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tree(): array;

    public function find(string $id): ?Area;

    public function findByCode(string $code): ?Area;

    /**
     * @return Collection<int, Area>
     */
    public function search(string $term, int $limit = 10): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Area;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Area $area, array $data): Area;

    public function delete(Area $area): void;

    /**
     * @return array<int, string>
     */
    public function descendantIds(Area $area): array;

    public function hasChildren(Area $area): bool;
}
