<?php

declare(strict_types=1);

namespace App\AdminModule\Components;

/**
 * Factory komponenty pro správu skupin.
 */
interface IGroupsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): GroupsGridControl;
}
