<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()!== null && $this->user()->can('create-permission');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions')],
            'code' => ['required', 'string', 'max:255', Rule::unique('permissions')],
            'description' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): PermissionDTO
    {
        return new PermissionDTO(
            name: $this->validated('name'),
            code: $this->validated('code'),
            description: $this->validated('description'),
        );
    }
}
