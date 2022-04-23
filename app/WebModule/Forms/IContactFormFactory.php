<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

/**
 * Factory komponenty s formulářem pro kontaktní formulář.
 */
interface IContactFormFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): ContactForm;
}
