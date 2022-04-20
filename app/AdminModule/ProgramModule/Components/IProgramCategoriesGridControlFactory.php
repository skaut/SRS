<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Components;

/**
 * Factory komponenty pro správu kategorií
 */
interface IProgramCategoriesGridControlFactory
{
    /**
     * Vytvoří komponentu
     */
    public function create(): ProgramCategoriesGridControl;
}
