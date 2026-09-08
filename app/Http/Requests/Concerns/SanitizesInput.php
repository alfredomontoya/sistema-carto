<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Str;

trait SanitizesInput
{
    protected function sanitizeText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = strip_tags($value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    protected function sanitizeUsername(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return Str::lower(trim($value));
    }

    protected function sanitizeCode(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === '' ? $value : Str::upper($value);
    }

    protected function sanitizePhone(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits === '' ? null : $digits;
    }
}
