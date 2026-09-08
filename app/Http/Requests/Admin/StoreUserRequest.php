<?php

namespace App\Http\Requests\Admin;

use App\Repositories\Contracts\UserRepository;
use App\Http\Requests\Concerns\SanitizesInput;
use App\Services\UserService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class StoreUserRequest extends FormRequest
{
    use SanitizesInput;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->sanitizeText($this->input('name')),
            'username' => $this->sanitizeUsername($this->input('username')),
            'phone' => $this->sanitizePhone($this->input('phone')),
            'address' => $this->sanitizeText($this->input('address')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(UserRepository $users): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9._-]+$/i',
                function (string $attribute, mixed $value, $fail) use ($users): void {
                    if ($users->findByEmail(UserService::emailFor($value)) !== null) {
                        $fail('El usuario ya se encuentra registrado.');
                    }
                },
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'position_id' => ['nullable', 'uuid', 'exists:positions,id'],
        ];
    }
}
