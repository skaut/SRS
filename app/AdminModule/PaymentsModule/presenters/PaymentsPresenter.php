<?php

declare(strict_types=1);

namespace App\AdminModule\PaymentsModule\Presenters;

use App\AdminModule\PaymentsModule\Components\IPaymentsGridControlFactory;
use App\AdminModule\PaymentsModule\Components\PaymentsGridControl;
use App\AdminModule\PaymentsModule\Presenters\PaymentsBasePresenter;

/**
 * Presenter obsluhující správu plateb.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PaymentsPresenter extends PaymentsBasePresenter
{
    /**
     * @var IPaymentsGridControlFactory
     * @inject
     */
    public $paymentsGridControlFactory;


    protected function createComponentPaymentsGrid() : PaymentsGridControl
    {
        return $this->paymentsGridControlFactory->create();
    }
}
