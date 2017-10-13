<?php

namespace App\AdminModule\ConfigurationModule\Forms;


/**
 * Factory komponenty s formulářem pro nastavení platby.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IPaymentFormFactory
{
    /**
     * Vytvoří komponentu.
     * @return mixed
     */
    public function create();
}
