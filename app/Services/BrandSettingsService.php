<?php

namespace App\Services;

use App\Repositories\Contracts\SettingsRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BrandSettingsService
{
    public function __construct(
        private readonly SettingsRepository $settings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        return [
            'app_name' => $this->settings->get('brand.app_name', config('brand.app_name')),
            'primary_color' => $this->settings->get('brand.primary_color', config('brand.primary_color')),
            'secondary_color' => $this->settings->get('brand.secondary_color', config('brand.secondary_color')),
            'logo_file' => $this->settings->get('brand.logo', config('brand.logo')),
            'favicon_file' => $this->settings->get('brand.favicon', config('brand.favicon')),
        ];
    }

    /**
     * @return array{logo_url: string|null, favicon_url: string|null}
     */
    public function urls(): array
    {
        $brand = $this->get();

        return [
            'logo_url' => $brand['logo_file'] !== null
                ? asset('storage/brand/'.$brand['logo_file'])
                : null,
            'favicon_url' => $brand['favicon_file'] !== null
                ? asset('storage/brand/'.$brand['favicon_file'])
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function withUrls(): array
    {
        return array_merge($this->get(), $this->urls());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, ?UploadedFile $logo = null, ?UploadedFile $favicon = null): void
    {
        $values = [];

        if (isset($data['app_name'])) {
            $values['brand.app_name'] = $data['app_name'];
        }

        if (isset($data['primary_color']) && $this->isHexColor($data['primary_color'])) {
            $values['brand.primary_color'] = $data['primary_color'];
        }

        if (isset($data['secondary_color']) && $this->isHexColor($data['secondary_color'])) {
            $values['brand.secondary_color'] = $data['secondary_color'];
        }

        if ($logo !== null) {
            $this->deleteStored('brand.logo');
            $values['brand.logo'] = $logo->store('brand', 'public');
        }

        if ($favicon !== null) {
            $this->deleteStored('brand.favicon');
            $values['brand.favicon'] = $favicon->store('brand', 'public');
        }

        $this->settings->setMany($values);
    }

    private function isHexColor(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1;
    }

    private function deleteStored(string $key): void
    {
        $current = $this->settings->get($key);
        if (is_string($current) && $current !== '' && str_starts_with($current, 'brand/')) {
            Storage::disk('public')->delete($current);
        }
    }
}
