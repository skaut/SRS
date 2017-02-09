<?php

namespace App\WebModule\Components;


interface IProgramsContentControlFactory
{
    /**
     * @return ProgramsContentControl
     */
    function create();
}