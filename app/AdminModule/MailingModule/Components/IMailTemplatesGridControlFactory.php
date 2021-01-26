<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Components;

/**
 * Factory komponenty pro správu automatických e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IMailTemplatesGridControlFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): MailTemplatesGridControl;
}
