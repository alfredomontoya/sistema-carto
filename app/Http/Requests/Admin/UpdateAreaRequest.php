<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use App\Http\Requests\Concerns\SanitizesInput;
use App\Models\Position;
use App\Services\AreaService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAreaRequest extends FormRequest
{
    use SanitizesInput;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->sanitizeText($this->input('name')),
            'code' => $this->sanitizeCode($this->input('code')),
            'description' => $this->sanitizeText($this->input('description')),
            'positions' => $this->sanitizePositionCodes($this->input('positions')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $areaId = $this->route('area');
        $defaults = AreaService::defaultPositionCodes();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9._-]+$/',
                Rule::unique('areas', 'code')->ignore($this->route('area')),
            ],
            'parent_id' => ['nullable', 'uuid', 'exists:areas,id'],
            'numbering_area_id' => ['nullable', 'uuid', 'exists:areas,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'reset_annually' => ['boolean'],
            'positions' => ['required', 'array', 'min:1'],
            'positions.*' => [
                'required',
                'string',
                'distinct',
                function (string $attribute, mixed $value, Closure $fail) use ($defaults, $areaId): void {
                    if (in_array($value, $defaults, true)) {
                        return;
                    }

                    if (is_string($value) && str_starts_with($value, 'id:')) {
                        $exists = Position::where('id', substr($value, 3))
                            ->where('area_id', $areaId)
                            ->exists();

                        if ($exists) {
                            return;
                        }
                    } elseif (is_string($value) && Position::whereRaw('LOWER(code) = ?', [$value])->where('area_id', $areaId)->exists()) {
                        return;
                    }

                    $fail('El puesto seleccionado no es válido.');
                },
            ],
        ];
    }

    /**
     * @return array<int, string>|mixed
     */
    private function sanitizePositionCodes(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        return array_values(array_map(function (mixed $code): mixed {
            if (! is_string($code)) {
                return $code;
            }

            $code = trim($code);

            if (str_starts_with(strtolower($code), 'id:')) {
                return 'id:'.trim(substr($code, 3));
            }

            return mb_strtolower($code);
        }, $value));
    }
}
