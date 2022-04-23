<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s kontaktním formulářem.
 */
interface IContactFormContentControlFactory
{
    public function create(): ContactFormContentControl;
}
