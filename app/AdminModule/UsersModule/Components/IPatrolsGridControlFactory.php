<?php

declare(strict_types=1);

namespace App\AdminModule\UsersModule\Components;

/**
 * Factory komponenty pro správu družin.
 */
interface IPatrolsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): PatrolsGridControl;
}
