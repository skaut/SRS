<?php

namespace App\AdminModule\CMSModule\Components;


interface IFaqGridControlFactory
{
    /**
     * @return FaqGridControl
     */
    function create();
}