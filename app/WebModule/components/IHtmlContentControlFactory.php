<?php

namespace App\WebModule\Components;


/**
 * Factory komponenty s HTML.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
interface IHtmlContentControlFactory
{
    /**
     * @return HtmlContentControl
     */
    function create();
}
