<?php

namespace App\DTO\Permission;

use App\DTO\BaseDTO;

class PermissionDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description = null,
    ) {}
}
