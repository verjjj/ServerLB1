<?php

namespace App\DTO\Role;

use App\DTO\BaseDTO;

class RoleDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description = null,
    ) {}
}
