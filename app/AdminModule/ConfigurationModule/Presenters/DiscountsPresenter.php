<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\DiscountsGridControl;
use App\AdminModule\ConfigurationModule\Components\IDiscountsGridControlFactory;
use App\AdminModule\ConfigurationModule\Forms\DiscountForm;
use App\AdminModule\ConfigurationModule\Forms\IDiscountFormFactory;
use Nette\DI\Attributes\Inject;

/**
 * Presenter obsluhující správu slev
 */
class DiscountsPresenter extends ConfigurationBasePresenter
{
    #[Inject]
    public IDiscountsGridControlFactory $discountsGridControlFactory;

    #[Inject]
    public IDiscountFormFactory $discountFormFactory;

    protected function createComponentDiscountsGrid(): DiscountsGridControl
    {
        return $this->discountsGridControlFactory->create();
    }

    protected function createComponentDiscountForm(): DiscountForm
    {
        $control = $this->discountFormFactory->create((int) $this->getParameter('id'));

        $control->onSave[] = function (): void {
            $this->flashMessage('admin.configuration.discounts_saved', 'success');
            $this->redirect('Discounts:default');
        };

        $control->onConditionError[] = function (): void {
            $this->flashMessage('admin.configuration.discounts_condition_format', 'danger');
        };

        return $control;
    }
}
