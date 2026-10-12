<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RestoreBackupRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'string', 'regex:/^respaldo-\d{8}-\d{6}\.sql(\.gz)?$/'],
            'confirm' => ['required', 'accepted'],
        ];
    }
}
