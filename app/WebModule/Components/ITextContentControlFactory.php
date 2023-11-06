<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty obsahu s textem.
 */
interface ITextContentControlFactory
{
    public function create(): TextContentControl;
}
