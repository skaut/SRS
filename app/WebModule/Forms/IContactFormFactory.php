<?php

declare(strict_types=1);

namespace App\WebModule\Forms;

/**
 * Factory komponenty s formulářem pro kontaktní formulář.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IContactFormFactory
{
    /**
     * Vytvoří komponentu.
     */
    public function create(): ContactForm;
}
