<?php

namespace App\Http\Requests\Admin;

use App\Models\Position;
use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
{
    use SanitizesInput;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->sanitizeText($this->input('name')),
            'code' => $this->sanitizePositionCode($this->input('code')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $position = $this->route('position');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[A-Z0-9._-]+$/i',
                Rule::unique('positions', 'code')
                    ->ignore($position)
                    ->where(
                        fn ($query) => $query->where(
                            'area_id',
                            $this->input('area_id', $position instanceof Position ? $position->area_id : null)
                        )
                    ),
            ],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    private function sanitizePositionCode(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = trim($value);

        return $value === '' ? $value : mb_strtolower($value);
    }
}
