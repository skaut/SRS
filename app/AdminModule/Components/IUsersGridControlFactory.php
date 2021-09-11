<?php

declare(strict_types=1);

namespace App\AdminModule\Components;

/**
 * Factory komponenty pro správu uživatelů.
 */
interface IUsersGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): UsersGridControl;
}
