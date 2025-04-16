<?php

namespace App\Http\Requests;

use App\DTO\UserResourceDTO;
use App\DTO\RegisterResourceDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('create-user');
    }

    public function rules(): array
    {
        //dd($this->all());
        return [
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'password' => ['required', 'confirmed', Password::min(8)],
            'birthday' => ['required', 'date'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ];
    }

    public function toDTO(): UserResourceDTO
    {
        //return $this->all();
        return new UserResourceDTO(
            username: $this->validated('name'),
            email: $this->validated('email'),
        );
    }
}
