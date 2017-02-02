<?php

namespace App\AdminModule\ConfigurationModule\Presenters;


use App\AdminModule\ConfigurationModule\Components\ICustomInputsGridControlFactory;
use App\Model\Settings\CustomInput\CustomInputRepository;

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

    protected function createComponentCustomInputsGrid($name)
    {
        return $this->customInputsGridControlFactory->create($name);
    }
}