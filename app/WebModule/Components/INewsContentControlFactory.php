<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s aktualitami.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface INewsContentControlFactory
{
    public function create(): NewsContentControl;
}
