<?php

declare(strict_types=1);

namespace App\AdminModule\GroupsModule\Components;

/**
 * Factory komponenty pro správu místností.
 */
interface IGroupsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): GroupsGridControl;
}
