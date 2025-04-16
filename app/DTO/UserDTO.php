<?php

namespace App\DTO;

use App\Models\User;
class UserDTO
{
    public function __construct(
        public int $id,
        public string $username,
        public string $email,
        public string $birthday,
        public ?string $deleted_at = null,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            $user->id,
            $user->username,
            $user->email,
            $user->birthday,
            $user->deleted_at?->toDateTimeString()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'birthday' => $this->birthday,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
