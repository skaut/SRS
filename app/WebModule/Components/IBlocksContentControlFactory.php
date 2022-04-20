<?php

declare(strict_types=1);

namespace App\WebModule\Components;

/**
 * Factory komponenty s podrobnostmi o programových blocích
 */
interface IBlocksContentControlFactory
{
    public function create(): BlocksContentControl;
}
