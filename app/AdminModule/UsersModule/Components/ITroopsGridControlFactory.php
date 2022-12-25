<?php

declare(strict_types=1);

namespace App\AdminModule\UsersModule\Components;

/**
 * Factory komponenty pro správu skupin.
 */
interface ITroopsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): TroopsGridControl;
}
