<?php

namespace App\WebModule\Components;


/**
 * Rozhraní komponenty s obrázkem.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IImageContentControlFactory
{
    /**
     * @return ImageContentControl
     */
    function create();
}