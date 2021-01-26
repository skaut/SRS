<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s přehledem lektorů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface ILectorsContentControlFactory
{
    public function create(): LectorsContentControl;
}
