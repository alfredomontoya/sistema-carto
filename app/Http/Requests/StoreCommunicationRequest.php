<?php

namespace App\Http\Requests;

use App\Models\Communication;
use App\Http\Requests\Concerns\SanitizesInput;
use App\Services\CommunicationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommunicationRequest extends FormRequest
{
    use SanitizesInput;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reference' => $this->sanitizeText($this->input('reference')),
            'recipient_name' => $this->sanitizeText($this->input('recipient_name')),
            'recipient_position' => $this->sanitizeText($this->input('recipient_position')),
            'area_destino_nombre' => $this->sanitizeText($this->input('area_destino_nombre')),
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
            'type' => ['required', Rule::in([Communication::TYPE_INTERNAL, Communication::TYPE_EXTERNAL])],
            'reference' => ['required', 'string', 'max:1000'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_position' => ['required', 'string', 'max:255'],
            'recipient_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'area_destino_id' => ['nullable', 'uuid', 'exists:areas,id'],
            'area_destino_nombre' => ['nullable', 'string', 'max:255'],
            'file' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp',
                'max:10240',
            ],
        ];
    }
}
