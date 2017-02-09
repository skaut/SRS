<?php

namespace App\WebModule\Components;


interface ITextContentControlFactory
{
    /**
     * @return TextContentControl
     */
    function create();
}