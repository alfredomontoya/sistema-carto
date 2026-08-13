<?php

namespace App\Http\Requests\Admin;

use App\Models\Area;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAreaRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:areas,code', 'regex:/^[a-z0-9._-]+$/'],
            'parent_id' => ['nullable', 'uuid', 'exists:areas,id'],
            'numbering_area_id' => ['nullable', 'uuid', 'exists:areas,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'reset_annually' => ['boolean'],
        ];
    }
}
