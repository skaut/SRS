<?php

declare(strict_types=1);

namespace App\AdminModule\Components;

/**
 * Factory komponenty pro správu uživatelů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IUsersGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create() : UsersGridControl;
}
