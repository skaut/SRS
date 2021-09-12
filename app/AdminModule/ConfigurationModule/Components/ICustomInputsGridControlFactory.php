<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;

/**
 * Factory komponenty pro správu vlastních polí přihlášky.
 */
interface ICustomInputsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): CustomInputsGridControl;
}
