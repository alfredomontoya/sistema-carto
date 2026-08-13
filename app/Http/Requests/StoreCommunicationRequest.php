<?php

namespace App\Http\Requests;

use App\Models\Communication;
use App\Services\CommunicationService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommunicationRequest extends FormRequest
{
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
            'recipient_position' => ['nullable', 'string', 'max:255'],
            'recipient_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'file' => [
                'nullable',
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp',
                'max:10240',
            ],
        ];
    }
}
