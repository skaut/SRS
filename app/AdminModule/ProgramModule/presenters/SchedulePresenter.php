<?php

namespace App\AdminModule\ProgramModule\Presenters;





class SchedulePresenter extends ProgramBasePresenter
{
    public function renderDefault() {
        $this->template->containerAttributes = 'ng-app="scheduleApp" ng-controller="AdminScheduleCtrl"';
    }
}