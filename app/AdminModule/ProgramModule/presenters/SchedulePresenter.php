<?php

namespace App\AdminModule\ProgramModule\Presenters;


use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\ProgramRepository;

class SchedulePresenter extends ProgramBasePresenter
{
    /**
     * @var ProgramRepository
     * @inject
     */
    public $programRepository;

    /**
     * @var BlockRepository
     * @inject
     */
    public $blockRepository;

    public function startup()
    {
        parent::startup();

        $this->template->results = $this->blockRepository->findAllOrderedByName();
    }

    public function renderDefault() {
        $this->template->containerAttributes = 'ng-app="scheduleApp" ng-controller="AdminScheduleCtrl"';
    }

    public function handleSearch($text, $unassignedOnly)
    {
        $unassignedOnly = filter_var($unassignedOnly, FILTER_VALIDATE_BOOLEAN);
        $this->template->results = $this->blockRepository->findByLikeNameOrderedByName($text, $unassignedOnly);
        $this->redrawControl('results');
    }
}