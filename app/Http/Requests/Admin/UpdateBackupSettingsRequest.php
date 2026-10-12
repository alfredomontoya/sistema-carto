<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBackupSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'drive' => ['nullable', 'string', 'size:1', 'regex:/^[A-Za-z]$/'],
            'max' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
