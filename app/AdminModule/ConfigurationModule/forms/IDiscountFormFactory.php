<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

/**
 * Factory komponenty s formulářem pro úpravu slevy.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IDiscountFormFactory
{
    /**
     * Vytvoří komponentu.
     * @param $id
     * @return mixed
     */
    public function create($id);
}
