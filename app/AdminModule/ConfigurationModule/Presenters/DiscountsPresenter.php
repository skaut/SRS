<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\DiscountsGridControl;
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
    /** @inject */
    public IDiscountsGridControlFactory $discountsGridControlFactory;

    /** @inject */
    public IDiscountFormFactory $discountFormFactory;

    protected function createComponentDiscountsGrid() : DiscountsGridControl
    {
        return $this->discountsGridControlFactory->create();
    }

    protected function createComponentDiscountForm() : DiscountForm
    {
        $control = $this->discountFormFactory->create((int) $this->getParameter('id'));

        $control->onSave[] = function () : void {
            $this->flashMessage('admin.configuration.discounts_saved', 'success');
            $this->redirect('Discounts:default');
        };

        $control->onConditionError[] = function (DiscountForm $control) : void {
            $this->flashMessage('admin.configuration.discounts_condition_format', 'danger');

            if ($control->id) {
                $this->redirect('Discounts:edit', ['id' => $control->id]);
            } else {
                $this->redirect('Discounts:add');
            }
        };

        return $control;
    }
}
