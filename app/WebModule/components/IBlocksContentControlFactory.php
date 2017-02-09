<?php

namespace App\WebModule\Components;


interface IBlocksContentControlFactory
{
    /**
     * @return BlocksContentControl
     */
    function create();
}