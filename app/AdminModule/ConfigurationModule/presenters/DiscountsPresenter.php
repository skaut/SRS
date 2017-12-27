<?php

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\IDiscountsGridControlFactory;
use App\AdminModule\ConfigurationModule\Forms\DiscountForm;
use App\AdminModule\ConfigurationModule\Forms\IDiscountFormFactory;


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
     * @var IDiscountFormFactory
     * @inject
     */
    public $discountFormFactory;


    protected function createComponentDiscountsGrid()
    {
        return $this->discountsGridControlFactory->create();
    }

    protected function createComponentDiscountForm()
    {
        $control = $this->discountFormFactory->create($this->getParameter('id'));

        $control->onSave[] = function () {
            $this->flashMessage('admin.configuration.discounts_saved', 'success');
            $this->redirect('Discounts:default');
        };

        $control->onConditionError[] = function (DiscountForm $control) {
            $this->flashMessage('admin.configuration.discounts_condition_format', 'danger');

            if ($control->id)
                $this->redirect('Discounts:edit', ['id' => $control->id]);
            else
                $this->redirect('Discounts:add');
        };

        return $control;
    }
}
