<?php
declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;


/**
 * Factory komponenty pro správu slev.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IDiscountsGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return DiscountsGridControl
     */
    public function create();
}
