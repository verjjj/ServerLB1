<?php

namespace App\DTO\Role;

use App\DTO\BaseCollectionDTO;
use App\DTO\Role\RoleDTO;
use Illuminate\Support\Collection;

class RoleCollectionDTO extends BaseCollectionDTO
{
    /**
     * Конструктор коллекции DTO для ролей.
     *
     * @param Collection $roles Коллекция моделей Role.
     */
    public function __construct(Collection $roles)
    {
        // Преобразуем каждую модель Role в RoleDTO
        $items = $roles->map(function ($role) {
            return RoleDTO::fromModel($role);
        });

        // Передаем преобразованные DTO в родительский класс
        parent::__construct($items);
    }
}
