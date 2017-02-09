<?php

namespace App\WebModule\Components;


interface IHtmlContentControlFactory
{
    /**
     * @return HtmlContentControl
     */
    function create();
}