<?php

namespace App\AdminModule\CMSModule\Forms;


interface IPageFormFactory
{
    /**
     * @return PageForm
     */
    function create($id, $area);
}