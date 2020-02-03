<?php

declare(strict_types=1);

namespace App\AdminModule\Components;

/**
 * Factory komponenty pro správu rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IRolesGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create() : RolesGridControl;
}
