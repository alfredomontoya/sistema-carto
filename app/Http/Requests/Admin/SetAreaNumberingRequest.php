<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SetAreaNumberingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'communication_type_id' => ['required', 'uuid', 'exists:type_communications,id'],
            'value' => ['required', 'integer', 'min:0', 'max:999999'],
            'force' => ['boolean'],
        ];
    }
}
