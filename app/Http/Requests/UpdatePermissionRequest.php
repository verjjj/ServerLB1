<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\DTO\Permission\PermissionDTO;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('update-permission');
    }


    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('permissions')->ignore($this->permission)],
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('permissions')->ignore($this->permission)],
            'description' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): PermissionDTO
    {
        return new PermissionDTO(
            name: $this->validated('name', $this->permission->name),
            code: $this->validated('code', $this->permission->code),
            description: $this->validated('description', $this->permission->description),
        );
    }
}
