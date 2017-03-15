<?php

namespace App\AdminModule\CMSModule\Forms;


interface IPageFormFactory
{
    /**
     * @param $id
     * @param $area
     * @return mixed
     */
    function create($id, $area);
}