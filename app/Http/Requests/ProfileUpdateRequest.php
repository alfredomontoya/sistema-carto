<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use SanitizesInput;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => $this->sanitizePhone($this->input('phone')),
            'address' => $this->sanitizeText($this->input('address')),
            'recovery_email' => $this->sanitizeUsername($this->input('recovery_email')),
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
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'recovery_email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('users', 'recovery_email')->ignore($this->user()->id),
            ],
            'avatar_kind' => ['sometimes', 'required', 'string', \Illuminate\Validation\Rule::in(['gallery', 'upload'])],
            'avatar_value' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
