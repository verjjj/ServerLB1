<?php

namespace App\DTO;

class LogRequestDto
{
    public function __construct(
        public int $id,
        public string $full_url,
        public string $http_method,
        public ?string $controller_path,
        public ?string $controller_method,
        public ?string $request_body,
        public ?string $request_headers,
        public ?int $user_id,
        public ?string $ip_address,
        public ?string $user_agent,
        public ?int $response_status,
        public ?string $response_body,
        public ?string $response_headers,
        public ?string $called_at,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}
} 