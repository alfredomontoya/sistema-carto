<?php

namespace App\Http\Requests\Admin;

use App\Models\Area;
use App\Http\Requests\Concerns\SanitizesInput;
use App\Models\User;
use App\Services\AreaService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAreaRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:areas,code', 'regex:/^[A-Z0-9._-]+$/'],
            'parent_id' => ['nullable', 'uuid', 'exists:areas,id'],
            'numbering_area_id' => ['nullable', 'uuid', 'exists:areas,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'reset_annually' => ['boolean'],
            'positions' => ['required', 'array', 'min:1'],
            'positions.*' => ['required', 'string', 'distinct', Rule::in(AreaService::defaultPositionCodes())],
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

        return array_values(array_map(
            fn (mixed $code): mixed => is_string($code) ? mb_strtolower(trim($code)) : $code,
            $value,
        ));
    }
}
