<?php

namespace App\WebModule\Components;


/**
 * Factory komponenty s přehledem lektorů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface ILectorsContentControlFactory
{
    /**
     * @return LectorsContentControl
     */
    public function create();
}
