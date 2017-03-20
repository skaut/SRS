<?php

namespace App\WebModule\Components;


/**
 * Rozhraní komponenty s textem.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface ITextContentControlFactory
{
    /**
     * @return TextContentControl
     */
    function create();
}