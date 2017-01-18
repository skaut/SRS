<?php

namespace App\AdminModule\Presenters;

use App\AdminModule\Components\ICustomInputsGridControlFactory;
use App\AdminModule\Presenters\AdminBasePresenter;

class ConfigurationPresenter extends AdminBasePresenter
{
    /**
     * @var ICustomInputsGridControlFactory
     * @inject
     */
    public $customInputsGridControlFactory;

    public function beforeRender()
    {
        parent::beforeRender();

        $this->template->sidebarVisible = true;
    }

    public function renderSrs() {

    }

    public function renderSkautIs() {

    }

    public function createComponentCustomInputsGrid($name)
    {
        return $this->customInputsGridControlFactory->create();
    }
}