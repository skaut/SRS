<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s HTML.
 */
interface IHtmlContentControlFactory
{
    public function create(): HtmlContentControl;
}
