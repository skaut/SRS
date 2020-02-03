<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s podrobnostmi o programových blocích.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IBlocksContentControlFactory
{
    public function create() : BlocksContentControl;
}
