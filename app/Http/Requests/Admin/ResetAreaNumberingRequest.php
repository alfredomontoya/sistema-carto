<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResetAreaNumberingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'force' => ['boolean'],
            'types' => ['required', 'array', 'min:1'],
            'types.*' => ['string', 'in:ci,of'],
        ];
    }
}
