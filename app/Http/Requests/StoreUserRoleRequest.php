<?php

namespace App\Http\Requests;

use App\DTO\UserRole\UserRoleDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign-role');
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
        ];
    }

    public function toDTO(): UserRoleDTO
    {
        return new UserRoleDTO(
            user_id: $this->validated('user_id'),
            role_id: $this->validated('role_id')
        );
    }
}
