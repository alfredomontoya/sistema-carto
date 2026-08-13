<?php

namespace App\Http\Requests;

use App\Repositories\Contracts\UserRepository;
use App\Services\UserService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(UserRepository $users): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9._-]+$/i',
                function (string $attribute, mixed $value, $fail) use ($users): void {
                    $existing = $users->findByEmail(UserService::emailFor($value));
                    if ($existing !== null && $existing->id !== $this->user()->id) {
                        $fail('El usuario ya se encuentra registrado.');
                    }
                },
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'avatar_kind' => ['sometimes', 'required', 'string', \Illuminate\Validation\Rule::in(['gallery', 'upload'])],
            'avatar_value' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
