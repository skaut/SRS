<?php

declare(strict_types=1);

namespace App\AdminModule\PaymentsModule\Presenters;

use App\AdminModule\PaymentsModule\Components\IPaymentsGridControlFactory;
use App\AdminModule\PaymentsModule\Components\PaymentsGridControl;
use App\AdminModule\PaymentsModule\Forms\EditPaymentForm;
use Nette\Forms\Form;
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
     * @var EditPaymentForm
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
    protected function createComponentEditPaymentForm() : Form
    {
        $form = $this->editPaymentFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, stdClass $values) : void {
            if (! $form['cancel']->isSubmittedBy()) {
                $this->flashMessage('admin.payments.payments.saved', 'success');
            }

            $this->redirect('Payments:default');
        };

        return $form;
    }
}
