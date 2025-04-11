<?php

namespace App\DTO\Role;

class RoleDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $code,
        public ?string $description = null,
        public array $permissions = []
    ) {}

    /**
     * Создает DTO из модели Role.
     *
     * @param \App\Models\Role $role Модель роли.
     * @return self
     */
    public static function fromModel(\App\Models\Role $role): self
    {
        return new self(
            $role->id,
            $role->name,
            $role->code,
            $role->description,
            $role->permissions->pluck('code')->toArray() // Возвращаем только коды разрешений
        );
    }
}
