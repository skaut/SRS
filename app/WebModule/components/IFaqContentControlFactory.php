<?php

namespace App\WebModule\Components;


/**
 * Factory komponenty s FAQ.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IFaqContentControlFactory
{
    /**
     * @return FaqContentControl
     */
    function create();
}