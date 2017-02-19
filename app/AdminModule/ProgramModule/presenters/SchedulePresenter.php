<?php

namespace App\AdminModule\ProgramModule\Presenters;


use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\ProgramRepository;

class SchedulePresenter extends ProgramBasePresenter
{
    public function renderDefault() {
        $this->template->containerAttributes = 'ng-app="scheduleApp" ng-controller="AdminScheduleCtrl"';
    }
}