<?php
declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;


/**
 * Factory komponenty pro správu vlastních polí přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface ICustomInputsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return CustomInputsGridControl
     */
    public function create();
}
