<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Presenters;

use App\AdminModule\MailingModule\Components\IMailHistoryGridControlFactory;
use App\AdminModule\MailingModule\Components\MailHistoryGridControl;
use Nette\DI\Attributes\Inject;

/**
 * Presenter obsluhující historii e-mailů.
 */
class HistoryPresenter extends MailingBasePresenter
{
    #[Inject]
    public IMailHistoryGridControlFactory $mailHistoryGridControlFactory;

    protected function createComponentMailHistoryGrid(): MailHistoryGridControl
    {
        return $this->mailHistoryGridControlFactory->create();
    }
}
