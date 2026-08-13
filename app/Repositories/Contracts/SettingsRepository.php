<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface SettingsRepository
{
    /**
     * @return Collection<int, \App\Models\Setting>
     */
    public function all(): Collection;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    /**
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void;
}
