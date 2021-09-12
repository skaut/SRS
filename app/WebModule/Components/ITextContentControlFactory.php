<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s textem.
 */
interface ITextContentControlFactory
{
    public function create(): TextContentControl;
}
