<?php

namespace App\WebModule\Components;


/**
 * Rozhraní komponenty s přihláškou.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IApplicationContentControlFactory
{
    /**
     * @return ApplicationContentControl
     */
    function create();
}