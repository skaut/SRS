<?php

declare(strict_types=1);

namespace App\AdminModule\GroupsModule\Components;

/**
 * Factory komponenty pro správu kategorií.
 */
interface IStatusGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): StatusGridControl;
}
