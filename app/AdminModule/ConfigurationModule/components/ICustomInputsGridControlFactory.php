<?php

namespace App\AdminModule\ConfigurationModule\Components;


/**
 * Rozhraní komponenty pro správu vlastních polí přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface ICustomInputsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return CustomInputsGridControl
     */
    function create();
}