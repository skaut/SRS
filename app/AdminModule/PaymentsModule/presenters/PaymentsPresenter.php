<?php

declare(strict_types=1);

namespace App\AdminModule\PaymentsModule\Presenters;

use App\AdminModule\Forms\BaseForm;
use App\AdminModule\PaymentsModule\Components\IPaymentsGridControlFactory;
use App\AdminModule\PaymentsModule\Components\PaymentsGridControl;
use App\AdminModule\PaymentsModule\Forms\EditPaymentFormFactory;
use stdClass;
use Throwable;

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

    /**
     * @var EditPaymentFormFactory
     * @inject
     */
    public $editPaymentFormFactory;


    public function renderEdit(int $id) : void
    {
    }

    protected function createComponentPaymentsGrid() : PaymentsGridControl
    {
        return $this->paymentsGridControlFactory->create();
    }

    /**
     * @throws Throwable
     */
    protected function createComponentEditPaymentForm() : BaseForm
    {
        $form = $this->editPaymentFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (BaseForm $form, stdClass $values) : void {
            if ($form->isSubmitted() !== $form['cancel']) {
                $this->flashMessage('admin.payments.payments.saved', 'success');
            }

            $this->redirect('Payments:default');
        };

        return $form;
    }
}
