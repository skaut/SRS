<?php

namespace App\AdminModule\CMSModule\Components;

interface IPagesGridControlFactory
{
    /**
     * @return PagesGridControl
     */
    function create();
}