<?php

namespace App\AdminModule\CMSModule\Components;

interface IMailHistoryGridControlFactory
{
    /**
     * @return MailHistoryGridControl
     */
    function create();
}