<?php

namespace App\DTOs;

class UserDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $birthday,
        public ?string $avatar_url = null
    ) {}
} 