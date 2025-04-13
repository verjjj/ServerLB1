<?php

namespace App\DTO;

class ChangeLogDTO
{
    public function __construct(
    public string $entityType,
    public int $entityId,
    public array $before,
    public array $after,
    public string $action
    ) {}
}
