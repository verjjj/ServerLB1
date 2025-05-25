<?php

namespace App\DTO;

class LogRequestCollectionDto
{
    /** @var array<LogRequestDto> */
    public array $items;
    public int $total;
    public int $perPage;
    public int $currentPage;
    public int $lastPage;

    public function __construct(array $items, int $total, int $perPage, int $currentPage, int $lastPage)
    {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
    }
}
