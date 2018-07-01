<?php
declare(strict_types=1);

namespace App\WebModule\Components;


/**
 * Factory komponenty s přihláškou.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IApplicationContentControlFactory
{
    /**
     * @return ApplicationContentControl
     */
    public function create();
}
