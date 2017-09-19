<?php

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\IDiscountsGridControlFactory;
use App\AdminModule\ConfigurationModule\Forms\DiscountForm;
use Nette\Forms\Form;


/**
 * Presenter obsluhující správu slev.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DiscountsPresenter extends ConfigurationBasePresenter
{
    /**
     * @var IDiscountsGridControlFactory
     * @inject
     */
    public $discountsGridControlFactory;

    /**
     * @var DiscountForm
     * @inject
     */
    public $discountFormFactory;


    protected function createComponentDiscountsGrid()
    {
        return $this->discountsGridControlFactory->create();
    }

    protected function createComponentDiscountForm()
    {
        $form = $this->discountFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if (!$form['cancel']->isSubmittedBy())
                $this->flashMessage('admin.configuration.discounts_saved', 'success');

            $this->redirect('Discount:default');
        };

        return $form;
    }
}
