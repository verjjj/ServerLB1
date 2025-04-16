<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\DTO\Role\RoleDTO;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('update-role');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('roles')->ignore($this->role)],
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('roles')->ignore($this->role)],
            'description' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): RoleDTO
    {
        return new RoleDTO(
            name: $this->validated('name', $this->role->name),
            code: $this->validated('code', $this->role->code),
            description: $this->validated('description', $this->role->description),
        );
    }
}
