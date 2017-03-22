<?php

namespace App\WebModule\Components;


/**
 * Rozhraní komponenty s podrobnostmi o programových blocích.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IBlocksContentControlFactory
{
    /**
     * @return BlocksContentControl
     */
    function create();
}