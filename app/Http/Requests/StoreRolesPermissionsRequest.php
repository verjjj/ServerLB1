<?php

namespace App\Http\Requests;

use App\DTO\RolePermissionDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRolesPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('assign-permission');
    }

    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'permission_id' => ['required', 'integer', Rule::exists('permissions', 'id')],
        ];
    }

    public function toDTO(): RolePermissionDTO
    {
        return new RolePermissionDTO(
            role_id: $this->validated('role_id'),
            permission_id: $this->validated('permission_id')
        );
    }
}
