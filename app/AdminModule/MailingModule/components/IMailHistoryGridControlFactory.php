<?php
declare(strict_types=1);

namespace App\AdminModule\MailingModule\Components;


/**
 * Factory komponenty pro výpis historie e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IMailHistoryGridControlFactory
{
    /**
     * Vytvoří komponentu.
     * @return MailHistoryGridControl
     */
    public function create();
}
