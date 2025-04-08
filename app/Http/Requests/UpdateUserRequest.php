<?php

namespace App\Http\Requests;

use App\DTO\User\UserDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update-user');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($this->user)],
            'password' => ['sometimes', 'confirmed', Password::min(8)],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ];
    }

    public function toDTO(): UserDTO
    {
        return new UserDTO(
            name: $this->validated('name', $this->user->name),
            email: $this->validated('email', $this->user->email),
            password: $this->validated('password'),
            roles: $this->validated('roles', []),
            permissions: $this->validated('permissions', [])
        );
    }
}
