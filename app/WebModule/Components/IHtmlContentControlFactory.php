<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s HTML.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IHtmlContentControlFactory
{
    public function create(): HtmlContentControl;
}
