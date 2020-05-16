<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Presenters;

use App\AdminModule\MailingModule\Components\IMailHistoryGridControlFactory;
use App\AdminModule\MailingModule\Components\MailHistoryGridControl;

/**
 * Presenter obsluhující historii e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class HistoryPresenter extends MailingBasePresenter
{
    /** @inject */
    public IMailHistoryGridControlFactory $mailHistoryGridControlFactory;

    protected function createComponentMailHistoryGrid() : MailHistoryGridControl
    {
        return $this->mailHistoryGridControlFactory->create();
    }
}
