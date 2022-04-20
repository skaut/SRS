<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Components;

/**
 * Factory komponenty pro výpis historie e-mailů
 */
interface IMailHistoryGridControlFactory
{
    /**
     * Vytvoří komponentu
     */
    public function create(): MailHistoryGridControl;
}
