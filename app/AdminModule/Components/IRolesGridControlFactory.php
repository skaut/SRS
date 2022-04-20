<?php

declare(strict_types=1);

namespace App\AdminModule\Components;

/**
 * Factory komponenty pro správu rolí
 */
interface IRolesGridControlFactory
{
    /**
     * Vytvoří komponentu
     */
    public function create(): RolesGridControl;
}
