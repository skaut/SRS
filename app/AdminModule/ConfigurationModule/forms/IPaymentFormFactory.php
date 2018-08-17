<?php

declare(strict_types=1);

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
     */
    public function create() : PaymentForm;
}
