<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s FAQ.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IFaqContentControlFactory
{
    public function create() : FaqContentControl;
}
