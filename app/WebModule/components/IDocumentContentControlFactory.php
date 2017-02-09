<?php

namespace App\WebModule\Components;


interface IDocumentContentControlFactory
{
    /**
     * @return DocumentContentControl
     */
    function create();
}