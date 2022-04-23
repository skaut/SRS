<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s dokumenty.
 */
interface IDocumentContentControlFactory
{
    public function create(): DocumentContentControl;
}
