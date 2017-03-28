<?php

namespace App\AdminModule\ProgramModule\Presenters;


/**
 * Presenter obsluhjící správu harmonogramu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SchedulePresenter extends ProgramBasePresenter
{
    public function renderDefault()
    {
        $this->template->containerAttributes = 'ng-app="scheduleApp" ng-controller="AdminScheduleCtrl"';
    }
}