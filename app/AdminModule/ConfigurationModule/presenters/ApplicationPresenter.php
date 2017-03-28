<?php

namespace App\AdminModule\ConfigurationModule\Presenters;

use App\AdminModule\ConfigurationModule\Components\CustomInputsGridControl;
use App\AdminModule\ConfigurationModule\Components\ICustomInputsGridControlFactory;
use App\Model\Settings\CustomInput\CustomInputRepository;


/**
 * Presenter obsluhující nastavení přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationPresenter extends ConfigurationBasePresenter
{
    /**
     * @var CustomInputRepository
     * @inject
     */
    public $customInputRepository;

    /**
     * @var ICustomInputsGridControlFactory
     * @inject
     */
    public $customInputsGridControlFactory;


    /**
     * @return CustomInputsGridControl
     */
    protected function createComponentCustomInputsGrid()
    {
        return $this->customInputsGridControlFactory->create();
    }
}