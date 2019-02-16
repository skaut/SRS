<?php

declare(strict_types=1);

namespace App\AdminModule\ProgramModule\Presenters;

use App\Model\Settings\Settings;

/**
 * Presenter obsluhující správu harmonogramu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SchedulePresenter extends ProgramBasePresenter
{
    /**
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     */
    public function renderDefault() : void
    {
        $this->template->containerAttributes = 'ng-app="scheduleApp" ng-controller="AdminScheduleCtrl"';
        $this->template->isAllowedModifySchedule = $this->settingsRepository->getBoolValue(Settings::IS_ALLOWED_MODIFY_SCHEDULE);
    }
}
