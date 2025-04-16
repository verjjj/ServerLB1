<?php

namespace App\Http\Requests;

use App\DTO\UserRoleDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('update-user-role');
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'integer', Rule::exists('users', 'id')],
            'role_id' => ['sometimes', 'integer', Rule::exists('roles', 'id')],
        ];
    }

    public function toDTO(): UserRoleDTO
    {
        return new UserRoleDTO(
            user_id: $this->validated('user_id', $this->userRole->user_id),
            role_id: $this->validated('role_id', $this->userRole->role_id)
        );
    }
}
