<?php

namespace App\AdminModule\MailingModule\Presenters;

use App\AdminModule\MailingModule\Components\IMailHistoryGridControlFactory;


/**
 * Presenter obsluhující historii e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class HistoryPresenter extends MailingBasePresenter
{
    /**
     * @var IMailHistoryGridControlFactory
     * @inject
     */
    public $mailHistoryControlGridFactory;


    protected function createComponentMailHistoryGrid()
    {
        return $this->mailHistoryControlGridFactory->create();
    }
}
