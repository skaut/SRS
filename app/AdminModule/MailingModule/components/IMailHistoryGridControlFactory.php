<?php

namespace App\AdminModule\MailingModule\Components;


interface IMailHistoryGridControlFactory
{
    /**
     * @return MailHistoryGridControl
     */
    function create();
}