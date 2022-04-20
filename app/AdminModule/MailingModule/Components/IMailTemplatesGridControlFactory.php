<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Components;

/**
 * Factory komponenty pro správu automatických e-mailů
 */
interface IMailTemplatesGridControlFactory
{
    /**
     * Vytvoří komponentu
     */
    public function create(): MailTemplatesGridControl;
}
