<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\DTO\Role\RoleDTO;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create-role');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')],
            'code' => ['required', 'string', 'max:255', Rule::unique('roles')],
            'description' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): RoleDTO
    {
        return new RoleDTO(
            name: $this->validated('name'),
            code: $this->validated('code'),
            description: $this->validated('description'),
        );
    }
}
