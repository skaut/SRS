<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Presenters;

use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;

/**
 * Presenter obsluhující správu harmonogramu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SchedulePresenter extends ProgramBasePresenter
{
    /**
     * @throws SettingsException
     * @throws \Throwable
     */
    public function renderDefault() : void
    {
        $this->template->containerAttributes     = 'ng-app="scheduleApp" ng-controller="AdminScheduleCtrl"';
        $this->template->isAllowedModifySchedule = $this->settingsFacade->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE);
    }
}
