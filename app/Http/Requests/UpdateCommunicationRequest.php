<?php

namespace App\Http\Requests;

use App\Models\Communication;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommunicationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = Communication::whereKey($this->route('communication'))->value('type');

        return [
            'reference' => ['required', 'string', 'max:1000'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_position' => ['required', 'string', 'max:255'],
            'recipient_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'area_destino_id' => ['nullable', 'uuid', 'exists:areas,id'],
            'area_destino_nombre' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf($type === Communication::TYPE_INTERNAL),
            ],
            'remove_file' => ['nullable', 'boolean'],
            'file' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp',
                'max:10240',
            ],
        ];
    }
}
