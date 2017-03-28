<?php

namespace App\WebModule\Components;


/**
 * Factory komponenty s dokumenty.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IDocumentContentControlFactory
{
    /**
     * @return DocumentContentControl
     */
    function create();
}