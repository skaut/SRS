<?php

namespace App\AdminModule\CMSModule\Components;


interface INewsGridControlFactory
{
    /**
     * @return NewsGridControl
     */
    function create();
}