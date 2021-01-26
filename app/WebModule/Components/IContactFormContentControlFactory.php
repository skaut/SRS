<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s kontaktním formulářem.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IContactFormContentControlFactory
{
    public function create(): ContactFormContentControl;
}
