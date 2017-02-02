<?php

namespace App\AdminModule\ProgramModule\Presenters;


use App\AdminModule\ProgramModule\Components\IRoomsGridControlFactory;

class RoomsPresenter extends ProgramBasePresenter
{
    /**
     * @var IRoomsGridControlFactory
     * @inject
     */
    public $roomsGridControlFactory;

    protected function createComponentRoomsGrid($name)
    {
        return $this->roomsGridControlFactory->create($name);
    }
}