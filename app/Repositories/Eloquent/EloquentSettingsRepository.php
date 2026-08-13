<?php

namespace App\Repositories\Eloquent;

use App\Models\Setting;
use App\Repositories\Contracts\SettingsRepository;
use Illuminate\Database\Eloquent\Collection;

class EloquentSettingsRepository implements SettingsRepository
{
    public function all(): Collection
    {
        return Setting::all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $row = Setting::where('key', $key)->first();

        if ($row === null || $row->value === null) {
            return $default;
        }

        $decoded = json_decode($row->value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $row->value;
    }

    public function set(string $key, mixed $value): void
    {
        $encoded = is_array($value) || is_bool($value) ? json_encode($value) : (string) $value;

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $encoded],
        );
    }

    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }
}
