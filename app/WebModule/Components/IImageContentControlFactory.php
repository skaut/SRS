<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s obrázkem.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IImageContentControlFactory
{
    public function create(): ImageContentControl;
}
