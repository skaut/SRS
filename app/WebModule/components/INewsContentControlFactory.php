<?php

namespace App\WebModule\Components;


/**
 * Factory komponenty s aktualitami.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface INewsContentControlFactory
{
    /**
     * @return NewsContentControl
     */
    function create();
}