<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

/**
 * Factory komponenty pro správu místností.
 */
interface IRoomsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): RoomsGridControl;
}
