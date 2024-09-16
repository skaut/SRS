<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty pro správu clenu skupiny.
 */
interface IGroupUsersGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): GroupUsersGridControl;
}
