<?php

namespace App\DTO;

use Illuminate\Support\Collection;

class ChangeLogCollectionDTO
{
    public function __construct(public Collection $logs) {}
}
